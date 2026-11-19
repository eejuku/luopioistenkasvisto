#!/usr/bin/env python3
"""
Vanhan sivuston ohjaukset: ohjaukset/vanha-sivusto.csv → Apachen RewriteMap-tiedosto.

Julkaisuputki ajaa tämän palvelimella jokaisen julkaisun yhteydessä:

    python3 ohjaukset/tee-rewritemap.py

ja PR:issä pelkän tarkistuksen:

    python3 ohjaukset/tee-rewritemap.py --tarkista

Taulukon sarakkeet (otsikkorivi pakollinen, muut sarakkeet saavat olla mukana):
    vanha   vanhan sivuston polku, esim. /Sivut/jakalat/korpiluppo.html
    uusi    uuden sivuston osoite, esim. /laji/korpiluppo/  (tyhjä = ei vastinetta)
    valmis  kylla = ohjaa nyt, ei = vanha sivu näkyy arkistosta

Vain valmiit rivit päätyvät ohjaustiedostoon. Jos taulukossa on yksikin virhe,
ohjaustiedostoa ei kirjoiteta: edellinen versio jää voimaan, ja ajo päättyy virheeseen
(julkaisu näkyy GitHubissa punaisena). Sivusto ei siis koskaan rikkoudu taulukon takia.

Apache lukee tiedoston uudelleen automaattisesti, kun se muuttuu. Uudelleenkäynnistystä
ei tarvita.
"""
import argparse
import csv
import io
import os
import sys
import tempfile
from collections import Counter
from urllib.parse import quote

KANSIO = os.path.dirname(os.path.abspath(__file__))
OLETUS_CSV = os.path.join(KANSIO, "vanha-sivusto.csv")
OLETUS_MAP = os.path.join(KANSIO, "vanha-sivusto.map")

# Vanhan sivuston kopio on nyt osoitteessa puhde.com/vanhakasvisto/. Ohjaukset toimivat
# sekä alkuperäisillä poluilla (/Sivut/…) että tämän kopion poluilla.
ETULIITTEET = ["", "/vanhakasvisto"]

KYLLA = {"kylla", "kyllä", "k", "x", "yes", "y", "1", "true"}
EI = {"ei", "e", "", "no", "n", "0", "false"}


def lue_taulukko(polku):
    with open(polku, "rb") as f:
        raaka = f.read()
    try:
        teksti = raaka.decode("utf-8-sig")
    except UnicodeDecodeError:
        teksti = raaka.decode("cp1252")  # Excelin oletus Windowsissa
        print("Huom: taulukko ei ollut UTF-8-muodossa, luettiin Windows-merkistöllä.")
    ensimmainen = teksti.splitlines()[0] if teksti else ""
    erotin = ";" if ensimmainen.count(";") > ensimmainen.count(",") else ","  # suomalainen Excel käyttää ;
    return list(csv.DictReader(io.StringIO(teksti), delimiter=erotin))


def koodaa(polku):
    """Selain lähettää ääkköset prosenttikoodattuina (ä → %C3%A4)."""
    return quote(polku, safe="/%:@!$&'()*+,;=-._~")


def tarkista(rivit):
    virheet, ohjaukset = [], []
    if rivit and not {"vanha", "uusi", "valmis"} <= set(k.strip().lower() for k in rivit[0].keys() if k):
        return ["otsikkorivillä pitää olla sarakkeet vanha, uusi ja valmis"], []
    nahty = {}
    for n, r in enumerate(rivit, start=2):  # rivi 1 = otsikot
        r = {(k or "").strip().lower(): (v or "").strip() for k, v in r.items()}
        vanha, uusi, valmis = r.get("vanha", ""), r.get("uusi", ""), r.get("valmis", "").lower()
        if not vanha and not uusi:
            continue
        if not vanha.startswith("/"):
            virheet.append(f"rivi {n}: vanha-polun pitää alkaa /-merkillä: {vanha!r}")
            continue
        if any(c.isspace() for c in vanha + uusi):
            virheet.append(f"rivi {n}: osoitteessa on välilyönti: {vanha!r} → {uusi!r}")
            continue
        if vanha in nahty:
            virheet.append(f"rivi {n}: sama vanha polku on jo rivillä {nahty[vanha]}: {vanha}")
            continue
        nahty[vanha] = n
        if valmis not in KYLLA | EI:
            virheet.append(f"rivi {n}: valmis-sarakkeessa pitää olla kylla tai ei, nyt {valmis!r}")
            continue
        if valmis in KYLLA:
            if not uusi:
                virheet.append(f"rivi {n}: valmis = kylla, mutta uusi osoite puuttuu: {vanha}")
                continue
            if not (uusi.startswith("/") or uusi.startswith("https://")):
                virheet.append(f"rivi {n}: uuden osoitteen pitää alkaa / tai https://: {uusi!r}")
                continue
            ohjaukset.append((vanha, uusi, r.get("ryhma", "")))
    return virheet, ohjaukset


def tee_map(ohjaukset):
    rivit = ["# Tämä tiedosto tehdään automaattisesti tiedostosta vanha-sivusto.csv.",
             "# Älä muokkaa tätä, vaan taulukkoa. Ks. ohjaukset/LUEMINUT.md"]
    avaimet = set()
    for vanha, uusi, _ in ohjaukset:
        for etu in ETULIITTEET:
            for avain in {etu + vanha, koodaa(etu + vanha)}:
                if avain not in avaimet:
                    avaimet.add(avain)
                    rivit.append(f"{avain} {koodaa(uusi)}")
    return "\n".join(rivit) + "\n"


def main():
    p = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("--csv", default=OLETUS_CSV)
    p.add_argument("--map", default=OLETUS_MAP)
    p.add_argument("--tarkista", action="store_true", help="vain tarkistus, ei kirjoiteta")
    a = p.parse_args()

    rivit = lue_taulukko(a.csv)
    virheet, ohjaukset = tarkista(rivit)
    if virheet:
        print(f"Ohjaustaulukossa on {len(virheet)} virhettä. Ohjaustiedostoa EI päivitetty:")
        for v in virheet[:50]:
            print("  - " + v)
        return 1

    ryhmat = Counter(r for _, _, r in ohjaukset)
    print(f"Taulukossa {len(rivit)} riviä, joista {len(ohjaukset)} ohjataan nyt:")
    for ryhma, maara in sorted(ryhmat.items()):
        print(f"  {ryhma or '(muut)'}: {maara}")

    if a.tarkista:
        print("Tarkistus OK.")
        return 0

    sisalto = tee_map(ohjaukset)
    kansio = os.path.dirname(os.path.abspath(a.map))
    fd, tmp = tempfile.mkstemp(dir=kansio, prefix=".vanha-sivusto.", suffix=".tmp")
    with os.fdopen(fd, "w", encoding="utf-8") as f:
        f.write(sisalto)
    os.chmod(tmp, 0o644)
    os.replace(tmp, a.map)  # yhdellä kertaa: Apache ei koskaan näe puolikasta tiedostoa
    print(f"Ohjaustiedosto päivitetty: {a.map}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
