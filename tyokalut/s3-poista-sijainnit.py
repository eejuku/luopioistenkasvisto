#!/usr/bin/env python3
"""
Poistaa GPS-sijainnin S3:ssa olevista alkuperäisistä JPEG-kuvista.

Sama menetelmä kuin WordPressissä (wp-content/mu-plugins/kasvisto/kuvien-sijainnit.php):
vain sijaintitavut nollataan, kuvadataa ei pakata uudelleen. Tiedoston koko, laatu,
kuvaaja-, tekijänoikeus-, kamera- ja asentotiedot säilyvät.

Ajo AWS CloudShellissa (boto3 on valmiina, tunnukset tulevat konsolista):

    python3 s3-poista-sijainnit.py --bucket BUCKETIN-NIMI
        → kuivaharjoitus: listaa kuvat, joissa on sijainti, mitään ei muuteta

    python3 s3-poista-sijainnit.py --bucket BUCKETIN-NIMI --kirjoita --cloudfront JAKELUN-ID
        → ottaa alkuperäiset talteen kansioon ./sijainti-varmuuskopio/, kirjoittaa
          siivotut tilalle ja mitätöi ne CloudFrontin välimuistista

WordPressin tekemät pienennökset (nimi-300x300.jpg, nimi-scaled.jpg) ohitetaan, koska
WordPress on jo poistanut niistä kaikki metatiedot. --kaikki käy nekin läpi.
"""
import argparse
import csv
import os
import re
import struct
import sys
import time

TYYPPIEN_KOOT = {1: 1, 2: 1, 3: 2, 4: 4, 5: 8, 6: 1, 7: 1, 8: 2, 9: 4, 10: 8, 11: 4, 12: 8}
PIENENNOS = re.compile(r"-(\d+x\d+|scaled)\.jpe?g$", re.I)


def _nollaa_exif_gps(d, t, raja):
    jarj = bytes(d[t:t + 2])
    if jarj not in (b"II", b"MM"):
        return False
    e = "<" if jarj == b"II" else ">"

    def sis(p, n):
        return p >= 0 and n >= 0 and t + p + n <= raja

    def u16(p):
        return struct.unpack_from(e + "H", d, t + p)[0]

    def u32(p):
        return struct.unpack_from(e + "I", d, t + p)[0]

    if not sis(4, 4):
        return False
    ifd0 = u32(4)
    if not sis(ifd0, 2):
        return False
    gps = 0
    for i in range(u16(ifd0)):
        k = ifd0 + 2 + i * 12
        if not sis(k, 12):
            return False
        if u16(k) == 0x8825:
            gps = u32(k + 8)
            break
    if not gps or not sis(gps, 2):
        return False
    kentat = u16(gps)
    if kentat == 0 or not sis(gps, 2 + kentat * 12):
        return False
    for i in range(kentat):
        k = gps + 2 + i * 12
        koko = TYYPPIEN_KOOT.get(u16(k + 2), 1) * u32(k + 4)
        if koko > 4:
            a = u32(k + 8)
            if sis(a, koko):
                d[t + a:t + a + koko] = bytes(koko)
    n = 2 + kentat * 12
    d[t + gps:t + gps + n] = bytes(n)
    if sis(gps + n, 4):
        d[t + gps + n:t + gps + n + 4] = bytes(4)
    return True


_XMP_ELEMENTTI = re.compile(rb"<(exif(?:EX)?:GPS\w+)\b[^>]*?(?:/>|>.*?</\1\s*>)", re.S)
_XMP_ATTRIBUUTTI = re.compile(rb"(?<=\s)exif(?:EX)?:GPS\w+\s*=\s*(?:\"[^\"]*\"|'[^']*')")


def _tyhjenna_xmp_gps(xmp):
    tyhja = lambda m: b" " * len(m.group(0))
    return _XMP_ATTRIBUUTTI.sub(tyhja, _XMP_ELEMENTTI.sub(tyhja, xmp))


def poista_sijainti(data):
    """Palauttaa (uusi_data, löydöt) tai (None, löydöt), jos sijaintia ei ollut."""
    loydot = {"exif": False, "xmp": False}
    if len(data) < 4 or data[:2] != b"\xFF\xD8":
        return None, loydot
    d = bytearray(data)
    o = 2
    while o + 4 <= len(d):
        if d[o] != 0xFF:
            break
        m = d[o + 1]
        if m == 0xFF:
            o += 1
            continue
        if m in (0xD8, 0x01) or 0xD0 <= m <= 0xD7:
            o += 2
            continue
        if m in (0xDA, 0xD9):
            break
        pituus = struct.unpack_from(">H", d, o + 2)[0]
        alku, loppu = o + 4, o + 2 + pituus
        if pituus < 2 or loppu > len(d):
            break
        if m == 0xE1:
            if d[alku:alku + 6] == b"Exif\0\0":
                if _nollaa_exif_gps(d, alku + 6, loppu):
                    loydot["exif"] = True
            elif d[alku:alku + 28] == b"http://ns.adobe.com/xap/1.0/":
                xmp = bytes(d[alku:loppu])
                uusi = _tyhjenna_xmp_gps(xmp)
                if uusi != xmp:
                    d[alku:loppu] = uusi
                    loydot["xmp"] = True
        o = loppu
    if loydot["exif"] or loydot["xmp"]:
        return bytes(d), loydot
    return None, loydot


def main():
    p = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("--bucket", required=True)
    p.add_argument("--prefix", default="wp-content/uploads/")
    p.add_argument("--kirjoita", action="store_true", help="muuta tiedostot (muuten kuivaharjoitus)")
    p.add_argument("--cloudfront", metavar="JAKELUN_ID", help="mitätöi muutetut tiedostot CloudFrontista")
    p.add_argument("--kaikki", action="store_true", help="käy läpi myös WordPressin pienennökset")
    p.add_argument("--varmuuskopio", default="sijainti-varmuuskopio")
    a = p.parse_args()

    import boto3

    s3 = boto3.client("s3")
    versiointi = s3.get_bucket_versioning(Bucket=a.bucket).get("Status")
    if versiointi == "Enabled":
        print("HUOM: bucketissa on versiointi päällä. Vanhat versiot (sijainteineen) jäävät talteen,\n"
              "      kunnes ne poistetaan erikseen tai elinkaarisääntö poistaa ne.\n")

    sivuttaja = s3.get_paginator("list_objects_v2")
    avaimet = []
    for sivu in sivuttaja.paginate(Bucket=a.bucket, Prefix=a.prefix):
        for obj in sivu.get("Contents", []):
            k = obj["Key"]
            if re.search(r"\.jpe?g$", k, re.I) and (a.kaikki or not PIENENNOS.search(k)):
                avaimet.append(k)
    print(f"Tutkitaan {len(avaimet)} JPEG-kuvaa…")

    muutetut = []
    for i, k in enumerate(avaimet, 1):
        alku = s3.get_object(Bucket=a.bucket, Key=k, Range="bytes=0-262143")["Body"].read()
        _, loydot = poista_sijainti(alku)
        if not (loydot["exif"] or loydot["xmp"]):
            if i % 250 == 0:
                print(f"  {i}/{len(avaimet)}")
            continue

        kohde = s3.get_object(Bucket=a.bucket, Key=k)
        data = kohde["Body"].read()
        uusi, loydot = poista_sijainti(data)
        if uusi is None:
            continue
        mista = "+".join(x for x, v in loydot.items() if v)
        print(f"  sijainti ({mista}): {k}")
        muutetut.append(k)
        if not a.kirjoita:
            continue

        polku = os.path.join(a.varmuuskopio, k)
        os.makedirs(os.path.dirname(polku), exist_ok=True)
        with open(polku, "wb") as f:
            f.write(data)

        lisat = {"ContentType": kohde.get("ContentType", "image/jpeg")}
        for nimi in ("CacheControl", "ContentDisposition", "ContentEncoding", "ContentLanguage", "Expires"):
            if kohde.get(nimi):
                lisat[nimi] = kohde[nimi]
        if kohde.get("Metadata"):
            lisat["Metadata"] = kohde["Metadata"]
        if kohde.get("StorageClass"):
            lisat["StorageClass"] = kohde["StorageClass"]
        try:
            acl = s3.get_object_acl(Bucket=a.bucket, Key=k)
            if any(g["Grantee"].get("URI", "").endswith("/AllUsers") for g in acl.get("Grants", [])):
                lisat["ACL"] = "public-read"
        except Exception:
            pass  # ACL:t pois käytöstä bucketissa → näkyvyys tulee bucketin asetuksista
        s3.put_object(Bucket=a.bucket, Key=k, Body=uusi, **lisat)

    with open("sijainnit-raportti.csv", "w", newline="") as f:
        csv.writer(f).writerows([[k] for k in muutetut])
    print(f"\nSijainti löytyi {len(muutetut)} kuvasta (lista: sijainnit-raportti.csv).")

    if not a.kirjoita:
        print("Kuivaharjoitus: mitään ei muutettu. Aja uudelleen --kirjoita.")
        return
    print(f"Siivottu. Alkuperäiset talteen kansiossa ./{a.varmuuskopio}/")

    if a.cloudfront and muutetut:
        cf = boto3.client("cloudfront")
        for i in range(0, len(muutetut), 1000):
            erä = ["/" + k for k in muutetut[i:i + 1000]]
            cf.create_invalidation(
                DistributionId=a.cloudfront,
                InvalidationBatch={"Paths": {"Quantity": len(erä), "Items": erä},
                                   "CallerReference": f"kasvisto-sijainnit-{time.time()}-{i}"},
            )
        print(f"CloudFront-mitätöinti pyydetty {len(muutetut)} tiedostolle.")
    elif muutetut:
        print("Muista mitätöidä CloudFront (tai anna --cloudfront JAKELUN-ID).")


if __name__ == "__main__":
    sys.exit(main())
