# WebPageSchemaBuilder

Der `WebPageSchemaBuilder` erzeugt ein `WebPage`-Schema für einzelne TYPO3-Seiten.

---

## Zweck

Der Builder beschreibt die aktuelle Seite als Schema.org `WebPage`.

Er wird verwendet, wenn in den Seiteneigenschaften im Feld `tx_schema_schema_type` der Wert `webpage` ausgewählt wurde.

---

## Aktivierung

In den Seiteneigenschaften muss der Schema-Typ gesetzt werden:


---

## Verwendete Seitendaten

Der Builder verwendet Daten aus dem aktuellen Page-Record.

| Schema Property | Quelle |
| :--- | :--- |
| `@id` | aktuelle Seiten-URL + `#webpage` |
| `url` | aktuelle Seiten-URL |
| `name` | zuerst `seo_title`, danach Fallback auf `title` |
| `description` | Feld `description` |
| `isPartOf` | Referenz auf `WebSite` |
| `about` | Referenz auf `Organization` |


---

## Referenzen

Der Builder referenziert globale Schemas über stabile `@id`-Werte:


Diese globalen Schemas sollten z.B. über den `SiteSettingsSchemaBuilder` ausgegeben werden.

Wichtig ist, dass alle Builder dieselbe Schema-Base-URL verwenden.

---

## Hinweise

Wenn `seo_title` leer ist, wird der normale Seitentitel verwendet.

Wenn weder `seo_title` noch `title` vorhanden ist, wird kein `WebPage`-Schema erzeugt.

Wenn `description` leer ist, wird die Property nicht ausgegeben.
