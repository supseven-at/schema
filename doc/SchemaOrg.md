# Schema.org strukturierte Daten Dokumentation

Diese Dokumentation beschreibt die Generierung strukturierter Daten (JSON-LD) für die Suchmaschinenoptimierung (SEO). Die Generierung basiert auf der PHP-Bibliothek `spatie/schema-org`.

---

## 1. Überblick & Architektur

Die Extension `EXT:schema` stellt eine schlanke, vollständig entkoppelte und wiederverwendbare Core-Engine bereit.

* **Entkoppelte Core-Engine (`EXT:schema`):** Beinhaltet die zentrale Logik (`SchemaOrgService`, `SchemaContext`, `SchemaBuilderInterface`, `SchemaDataProcessor`, `Util`), besitzt jedoch bewusst **keine** Abhängigkeiten zu projektspezifischen Domain-Modellen oder Inhaltselementen.
* **Flexible Builder-Ablage (`EXT:theme` / Eigene Extensions):** Konkrete `SchemaBuilder`-Implementierungen liegen in der Theme-Extension (`EXT:theme`) oder in beliebigen anderen Extensions. Über Symfony Dependency Injection (DI) und das Tag `schema.builder` können Builder flexibel an jedem beliebigen Ort im Projekt definiert und automatisch geladen werden.

```
                     ┌──────────────────┐
                     │ SchemaOrgService │ (Registry/Koordinator)
                     └────────┬─────────┘
                              │
                     ┌────────▼─────────┐
                     │  SchemaContext   │ (Request/Site DTO)
                     └──────────────────┘
                              │
             ┌────────────────┼────────────────┬──────────────────────┐
             │                │                │                      │
   ┌─────────▼──────────────┐┌──────▼─────────┐┌─────▼──────────────┐┌──────▼─────────────┐
   │VideoObjectSchemaBuilder││...             ││...                 ││BuildYourOwn        │
   └────────────────────────┘└────────────────┘└────────────────────┘└────────────────────┘
```

---

## 2. Klassen-Übersicht

### 2.1 Core-Engine (`EXT:schema`)

| Klasse / Interface | Pfad | Beschreibung |
| :--- | :--- | :--- |
| `SchemaOrgService` | `Classes/Service/SchemaOrgService.php` | Zentraler Koordinator. Sammelt alle getaggten Builder (`schema.builder`), erzeugt den `SchemaContext`, iteriert über die Builder und injiziert das JSON-LD in den `PageRenderer`. |
| `SchemaContext` | `Classes/SchemaContext.php` | Unveränderliches Context-DTO für Request- und Site-Daten (Request, Site, Host, Sprache etc.). |
| `SchemaBuilderInterface` | `Classes/Builder/SchemaBuilderInterface.php` | Interface für alle Schema-Builder. Definiert `supports(mixed $item): bool` und `build(mixed $item, SchemaContext $context): ?BaseType`. |
| `SchemaDataProcessor` | `Classes/DataProcessing/SchemaDataProcessor.php` | DataProcessor für TypoScript (`schema-processor`), um Seiten- oder CType-Daten an den `SchemaOrgService` zu übergeben. |
| `Util` | `Classes/Utility/Util.php` | Hilfsklasse für Textbereinigung, ISO-8601-Datums- und Dauer-Formatierung sowie Provider-IDs (`@id`). |

### 2.2 Projektspezifische Schema-Builder (`EXT:theme`)

| Builder-Klasse | Pfad (EXT:schema)                                      | Erzeugter Schema.org Typ                                     |
| :--- |:-------------------------------------------------------|:-------------------------------------------------------------|
| `SiteSettingsSchemaBuilder` | `Classes/Schema/Builder/SiteSettingsSchemaBuilder.php` | diverse globale Schema, die in den Settings definiert werden |
| `WebPageSchemaBuilder` | `Classes/Schema/Builder/WebPageSchemaBuilder.php`      | `Webpage`                                                    |

---

## 3. Dependency Injection & Tagging (`schema.builder`)

Das System nutzt TYPO3 / Symfony Autowiring und Tagging, wodurch Entwickler SchemaBuilder von **überall im Codebase** bereitstellen können.

### 3.1 Tag-Definition in `EXT:schema`

In `Configuration/Services.php` von `EXT:schema` wird definiert, dass alle Klassen, die `SchemaBuilderInterface` implementieren, mit dem Tag `schema.builder` markiert werden:

```php
$services
    ->instanceof(SchemaBuilderInterface::class)
    ->tag('schema.builder');
```

Im `SchemaOrgService` werden alle registrierten Builder automatisch per `#[AutowireIterator('schema.builder')]` injiziert:

```php
public function __construct(
    protected PageRenderer $pageRenderer,
    #[AutowireIterator('schema.builder')]
    iterable $builders = []
) {
    $this->builders = is_array($builders) ? $builders : iterator_to_array($builders, false);
}
```

### 3.2 Bereitstellung in der Theme-Extension (oder eigenen Extensions)

In der konsumierenden Extension (z. B. `EXT:theme`) müssen die Builder lediglich über `Configuration/Services.php` geladen werden:

```php
// EXT:theme/Configuration/Services.php
return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->public()
        ->autowire()
        ->autoconfigure();

    $services
        ->instanceof(SchemaBuilderInterface::class)
        ->tag('schema.builder');

    // Lädt alle Builder im Namespace Supseven\Theme\Schema\Builder\
    $services->load('Supseven\\Theme\\Schema\\Builder\\', __DIR__ . '/../Classes/Schema/Builder/*');
};
```

> **Vorteil:** Der Entwickler kann Builder-Klassen in beliebigen Namespaces, Verzeichnissen oder Extensions anlegen. Sobald die Klasse `SchemaBuilderInterface` implementiert und per DI registriert ist, wird sie zur Laufzeit automatisch vom `SchemaOrgService` erkannt.

---

## 4. Dokumentierte Schematypen

* **[Globale Schema über Site Settings (SiteSettingsSchemaBuilder)](SiteSettingsSchemaBuilder.md)**
* **[Webpage (WebPageSchemaBuilder)](WebPageSchemaBuilder.md)**

---

## 5. Erweiterung um neue Schematypen

Um strukturierte Daten für ein neues Modell oder ein Inhaltselement hinzuzufügen, sind folgende Schritte erforderlich:

### Schritt 1: Builder-Klasse erstellen

Erstelle eine neue Klasse in der gewünschten Extension (z. B. `EXT:theme/Classes/Schema/Builder/MyModelSchemaBuilder.php`), die das `SchemaBuilderInterface` implementiert:

```php
namespace Supseven\Theme\Schema\Builder;

use Spatie\SchemaOrg\BaseType;
use Spatie\SchemaOrg\Schema;
use Supseven\Schema\Builder\SchemaBuilderInterface;
use Supseven\Schema\SchemaContext;
use MyVendor\MyExt\Domain\Model\MyModel;

class MyModelSchemaBuilder implements SchemaBuilderInterface
{
    public function supports(mixed $item): bool
    {
        return $item instanceof MyModel;
    }

    public function build(mixed $item, SchemaContext $context): ?BaseType
    {
        return Schema::thing()
            ->name($item->getTitle())
            ->description($item->getDescription());
    }
}
```

### Schritt 2: DI-Registrierung

Stelle sicher, dass die Klasse in der `Configuration/Services.php` der entsprechenden Extension geladen wird. Dank Autoconfiguration und Tagging wird der Builder automatisch beim `SchemaOrgService` registriert.


## 6. Seiteneigenschaften über `tx_schema_schema_type` erweitern

Neben Modell- oder Inhaltselement-basierten Buildern kann ein Schema auch direkt über die Seiteneigenschaften gesteuert werden.

Die Extension `EXT:schema` stellt dafür das Feld `tx_schema_schema_type` auf der Tabelle `pages` bereit. Über dieses Feld kann ein Redakteur pro Seite einen Schema-Typ auswählen.
Ein eigener Builder kann anschließend anhand dieses Feldwertes entscheiden, ob er für die aktuelle Seite zuständig ist.

Typischer Ablauf:

1. In einer eigenen Extension wird die Auswahlliste `tx_schema_schema_type` erweitert.
2. Der Redakteur wählt den neuen Schema-Typ in den Seiteneigenschaften.
3. Ein eigener Builder prüft in `supports()` auf den gespeicherten Wert.
4. Der Builder erzeugt das gewünschte Schema.org-Objekt.

### 6.1 Select-Option in eigener Extension ergänzen

In der eigenen Extension kann das Feld `tx_schema_schema_type` der Standardseiten per TCA-Override erweitert werden.

Beispiel:
```typo3_typoscript
TCEFORM.pages.tx_schema_schema_type.addItems {
        article = Article
    }
```

### 6.2 Builder für den neuen Seitentyp erstellen

Der Builder prüft anschließend auf den Wert aus `tx_schema_schema_type`.


## 7. Hinweise zu Referenzen und `@id`

Für globale Entitäten wie `Organization`, `WebSite` oder `LocalBusiness` sollten stabile `@id`-Werte verwendet werden.

