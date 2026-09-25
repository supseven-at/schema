# SiteSettingsSchemaBuilder

Der `SiteSettingsSchemaBuilder` erzeugt globale Schema.org-Objekte aus den TYPO3 Site Settings.

Er ist dafür gedacht, wiederkehrende, seitenübergreifende strukturierte Daten zentral über YAML zu konfigurieren, z.B.:

- `Organization`
- `LocalBusiness`
- `WebSite`
- `Person`
- `PostalAddress`
- `ContactPoint`
- `ImageObject`
- `Country`
- weitere von `spatie/schema-org` unterstützte Typen

Die Daten werden aus dem Site-Settings-Pfad `schema` gelesen.

---

## 1. Zweck

Der Builder ermöglicht es, globale JSON-LD-Schema-Daten deklarativ in YAML zu pflegen, ohne dafür pro Schema-Typ einen eigenen PHP-Builder schreiben zu müssen.

Beispielhafte globale Entitäten:

```yaml
schema:
  shared:
    name: "supseven e.U."
    url: "https://%env(DOMAIN_SUPSEVEN)%/"
    telephone: "+43 1 997 1052"
    email: "office@supseven.at"

    organization:
        type: Organization
        name: '%schema.shared.name%'
        url: '%schema.shared.url%'
        logo:
            type: ImageObject
            url: '%schema.shared.logo%'

     website:
        type: WebSite
        name: '%schema.shared.name%'
        url: '%schema.shared.url%'

     localbusiness:
        type: LocalBusiness
        name: '%schema.shared.name%'
        url: '%schema.shared.url%'
        image:
          type: ImageObject
          url: "https://www.supseven.at/fileadmin/_processed_/d/1/csm_Startseitegrafik_teamfoto_4948b1334f.webp"
        telephone: "%schema.shared.telephone%"
        email: "%schema.shared.email%"
        currenciesAccepted: "EUR"
        paymentAccepted: "Überweisung"
        priceRange: "€€€"
        areaServed:
          - type: Country
            name: "Österreich"
            sameAs: "https://www.wikidata.org/wiki/Q40"
          - type: Country
            name: "Deutschland"
            sameAs: "https://www.wikidata.org/wiki/Q183"
          - type: Country
            name: "Schweiz"
            sameAs: "https://www.wikidata.org/wiki/Q39"
```


Daraus erzeugt der Builder automatisch Schema.org-Objekte und fügt sie in den JSON-LD-Graph ein.

---

## 2. Datenquelle

Die Konfiguration muss in den TYPO3 Site Settings verfügbar sein.

Typischerweise wird eine eigene YAML-Datei in die Site Settings importiert:

```
imports:
    - resource: 'EXT:theme/Configuration/SiteConfig/Schema.yaml'
```

Die importierte Datei enthält dann den `schema`-Block:


---

## 3. Grundstruktur

Der Builder erwartet unterhalb von `schema` mehrere Top-Level-Einträge.

Jeder Eintrag mit einem `type` wird als eigenes Schema.org-Objekt interpretiert.


### Bedeutung der Top-Level-Keys

| Key | Bedeutung |
| :--- | :--- |
| `shared` | Gemeinsame Werte, die per Placeholder wiederverwendet werden können. Wird nicht als Schema erzeugt. |
| `organization` | Wird als Schema erzeugt, wenn `type` gesetzt ist. |
| `website` | Wird als Schema erzeugt, wenn `type` gesetzt ist. |
| `localbusiness` | Wird als Schema erzeugt, wenn `type` gesetzt ist. |
| beliebige weitere Keys | Werden als Schema erzeugt, sofern ein gültiger `type` gesetzt ist. |

---

## 4. Automatische Typ-Auflösung

Der `type` wird direkt als Klasse aus dem Namespace `Spatie\SchemaOrg` interpretiert.


Bsp: Spatie\SchemaOrg\WebSite
```yaml
type: WebSite
```


Das bedeutet: Jeder Typ muss als Klasse in `spatie/schema-org` existieren.

---

## 5. Placeholder

Werte können über einfache Placeholder aus `schema.shared` oder anderen Pfaden innerhalb von `schema` referenziert werden.

```yaml
shared:
    name: "Example GmbH"
    url: "https://www.example.com/"

organization:
    type: Organization
    name: "%schema.shared.name%"
    url: "%schema.shared.url%"
```

## 6. Listen

YAML-Listen werden als JSON-Arrays ausgegeben.

```yaml
sameAs:
    - "https://www.linkedin.com/company/example/"
    - "https://www.instagram.com/example/"
```

## 7. Automatische `@id`

Der Builder ergänzt automatisch eine `@id`

Die `@id` wird aus der zentralen Schema-Base-URL und dem YAML-Key gebildet.
Andere Builder können globale Schemas über deren `@id` referenzieren.


---

## 8. Wann sollte der SiteSettingsSchemaBuilder verwendet werden?

Der Builder eignet sich für globale, stabile Daten, die nicht von einer einzelnen Seite oder einem einzelnen Inhaltselement abhängen.

Gut geeignet:

- Unternehmensdaten
- Organisation
- Standortdaten
- Website-Daten
- Social-Media-Profile
- Kontaktinformationen
- Logo
- Gründer
- bediente Länder oder Regionen

Nicht ideal geeignet:

- Seitenspezifische `WebPage`-Daten
- Artikel oder Blogposts
- News
- Produkte mit wechselnden Daten
- dynamische Breadcrumbs
- Content-Elemente wie FAQ-Elemente oder Videos

Für solche Fälle sollte ein eigener Builder verwendet werden.

---

## 9. Zusammenspiel mit anderen Buildern

Der `SiteSettingsSchemaBuilder` erzeugt globale Entitäten.

Andere Builder können diese Entitäten referenzieren.

Beispiel:

- `SiteSettingsSchemaBuilder` erzeugt `Organization` mit `@id: https://www.example.com/#organization`
- `WebPageSchemaBuilder` referenziert diese Organization über `about`
- `ArticleSchemaBuilder` referenziert diese Organization über `author` und `publisher`

Dadurch entsteht ein zusammenhängender JSON-LD-Graph.


## 10. Fehlersuche

### Schema wird nicht ausgegeben

Prüfen:

1. Ist die Site-Settings-Datei importiert?
2. Existiert der Root-Key `schema`?
3. Hat der gewünschte Eintrag einen gültigen `type`?
4. Existiert der `type` als Klasse in `spatie/schema-org`?
5. Wird der `SiteSettingsSchemaBuilder` als Service registriert?
6. Läuft der `SchemaDataProcessor` auf der Seite?

