# Vanhan sivuston ohjaukset

`vanha-sivusto.csv` kertoo, mihin uuden sivuston sivuun kukin vanhan
luopioistenkasvisto.fi-sivuston sivu ohjataan.

| Sarake | Merkitys |
|---|---|
| `vanha` | vanhan sivun polku, esim. `/Sivut/jakalat/korpiluppo.html` |
| `uusi` | uuden sivuston sivu, esim. `/laji/korpiluppo/`. Tyhjä = ei vastinetta |
| `valmis` | `kylla` = ohjataan nyt · `ei` = vanha sivu näkyy arkistosta |
| `ryhma` | Putkilokasvit, Sammalet, Jäkälät, Piensienet tai Yleiset |
| `peruste` | miten vastine löytyi: `tieteellinen`, `suomi`, `tiedostonimi`, `isantakasvi`, `käsin`; tyhjä = ei löytynyt |
| `nimi` | vanhan sivun lajinimi tarkistamisen avuksi |

Vain sarakkeilla `vanha`, `uusi` ja `valmis` on merkitystä ohjauksille. Muut ovat
ihmisiä varten.

## Lajin ottaminen käyttöön

Kun uusi kortti on valmis (esim. jäkälän alkuperäiset kuvat on lisätty), vaihda sen
rivillä `ei` → `kylla`.

1. Avaa tämä tiedosto GitHubissa ja paina kynäkuvaketta (Edit).
2. Etsi rivi (Ctrl/Cmd+F), vaihda `ei` → `kylla`.
3. Paina **Commit changes…** ja valitse **Create a new branch … and start a pull request**.
4. PR:ssä ajetaan automaattinen tarkistus. Vihreä = kunnossa, yhdistä PR.
5. Julkaisu tekee ohjaukset palvelimelle muutamassa minuutissa.

Taulukon voi avata myös Excelissä. Tallenna se silloin muodossa **CSV UTF-8**.
Suomalaisen Excelin puolipiste-erotin kelpaa.

Jos uusi osoite on väärä, korjaa `uusi`-sarake. Jos vastinetta ei ole, jätä `uusi` tyhjäksi
ja `valmis` = `ei`: vanha sivu näkyy silloin edelleen arkistosta.

## Mitä taulukolle tapahtuu

- **PR:ssä** `tee-rewritemap.py --tarkista` tarkistaa taulukon (polut, kaksoisrivit,
  `kylla` ilman uutta osoitetta jne.). Virheestä tulee punainen rasti ja rivinumero.
- **Julkaisussa** `tee-rewritemap.py` kirjoittaa palvelimelle tiedoston
  `ohjaukset/vanha-sivusto.map` (ei ole gitissä). Apache lukee sen automaattisesti,
  kun se muuttuu. Jos taulukossa on virhe, vanha tiedosto jää voimaan.
- Ohjaus toimii sekä vanhoilla poluilla (`/Sivut/…`, kun luopioistenkasvisto.fi on
  siirretty) että nykyisen kopion poluilla (`/vanhakasvisto/Sivut/…`).
- Ohjaus on pysyvä (301), joten hakukoneet päivittävät tietonsa uuteen osoitteeseen.

## Apachen asetus (tehdään kerran)

`/etc/apache2/sites-enabled/000-default-le-ssl.conf`, `<VirtualHost>`-lohkon sisään:

```
# Luopioisten kasvisto: vanhan sivuston ohjaukset (ohjaukset/LUEMINUT.md)
RewriteEngine On
RewriteMap vanhasivusto "txt:/var/www/html/ohjaukset/vanha-sivusto.map"
RewriteCond "${vanhasivusto:%{REQUEST_URI}|-}" "!=-"
RewriteRule "^" "${vanhasivusto:%{REQUEST_URI}}" [R=301,L,NE]
```

## Taulukon synty (19.11.2026)

Taulukko tehtiin vertaamalla puhde.com/vanhakasvisto/-kopion kaikkia 2 662 sivua
uuden sivuston lajilistoihin (1 912 korttia). Ensin verrattiin tieteellistä nimeä
(myös synonyymejä), sitten suomenkielistä nimeä ja tiedostonimeä. Ryhmäsivut
yhdistettiin käsin. Putkilokasvit ja sammalet ovat `kylla`, jäkälät ja piensienet `ei`.
Rivit, joiden peruste on `suomi` tai `tiedostonimi`, kannattaa silmäillä läpi ennen kuin
ne otetaan käyttöön.
