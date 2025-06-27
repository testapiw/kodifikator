# 🇺🇦 Kodifikator Parser & Importer

**Parser and importer for the Codifier of Administrative-Territorial Units and Territorial Communities of Ukraine.**

This Symfony-based project automates the process of parsing, downloading, and importing the official Ukrainian  
**Кодифікатор адміністративно-територіальних одиниць та територій територіальних громад КАТОТТГ**  
into your application.

---

## Source

**Official government page:**  
🔗 [mindev.gov.ua](https://mindev.gov.ua/diialnist/rozvytok-mistsevoho-samovriaduvannia/kodyfikator-administratyvno-terytorialnykh-odynyts-ta-terytorii-terytorialnykh-hromad)

**License:** [CC BY 4.0](https://creativecommons.org/licenses/by/4.0/deed.en)

---

## Features

- Automatically parses the latest codifier release from the official site.
- Downloads and stores XLSX, PDF, DOCX versions of the document.
- Imports XLSX data into your database using Doctrine.
- Batch processing for optimized performance with large datasets.
- Symfony Console command and HTTP API endpoints available.

---

## Requirements

### PHP Extensions

- `php-gd`
- `php-zip`
- `php-xml`
- `php-mbstring`

### Composer Dependencies

```bash
composer require guzzlehttp/guzzle
composer require symfony/dom-crawler symfony/css-selector
composer require phpoffice/phpspreadsheet
```

---

## Installation

1. Clone the repository
2. Set the environment variable for storage:

```dotenv
# .env
KODIFIKATOR_PATH="var/kodifikator"
```
3. Register services in config/services.yaml

```yaml
services:
    # Automatically register Doctrine repositories
    Kodifikator\Repository\:
        resource: '../addon/kodifikator/src/Repository'
        tags: ['doctrine.repository_service']

    # Register services with parameters
    Kodifikator\Service\KodifikatorUploader:
        arguments:
            $storagePath: '%env(resolve:KODIFIKATOR_PATH)%'

    Kodifikator\Service\KodifikatorImport:
        arguments:
            $storagePath: '%env(resolve:KODIFIKATOR_PATH)%'

    Kodifikator\Domain\KodifikatorParser: ~

    Kodifikator\Service\KodifikatorManager: ~

    # Autowire other classes (e.g., Console, Controller, etc.)
    Kodifikator\:
        resource: '%kernel.project_dir%/vendor/addon/kodifikator/src/Repository'
        exclude:
            - '%kernel.project_dir%/vendor/addon/kodifikator/src/Entity'
            - '%kernel.project_dir%/vendor/addon/kodifikator/src/Repository'
```

3. Register Doctrine mapping in config/packages/doctrine.yaml:

```yaml
doctrine:
    orm:
        mappings:
            Kodifikator:
                is_bundle: false
                type: attribute
                dir:  '%kernel.project_dir%/vendor/addon/kodifikator/src/Entity'
                prefix: 'Kodifikator\Entity'
                alias: Kodifikator
```

```
php bin/console make:migration
```

4. Ensure the storage directory exists and is writable:

```bash
sudo mkdir -p var/kodifikator
sudo chown www-data:www-data var/kodifikator
```


Install via Composer:

```bash
composer require addon/kodifikator
```
---

## Usage

Run the full fetch and import process via Symfony Console:

```bash
php bin/console kodifikator:update
```

Or call via HTTP API (if exposed):

```http
GET  /kodifikator/parse     # Get available document links
POST /kodifikator/update    # Run full fetch & import process
```

---

## Optimization Notes

This project handles large XLSX datasets efficiently:

- **Batch inserts:** Processes 300 rows at a time
- **Hash upsert:** Prevents duplicates using computed hash key
- **Streaming:** XLSX rows are processed with minimal memory

Supports large files with 10,000+ rows.

---

## Structure

| Component               | Description                                       |
|------------------------|---------------------------------------------------|
| `KodifikatorParser`    | Parses the official government page               |
| `KodifikatorUploader`  | Downloads XLSX/PDF/DOCX files and stores metadata |
| `KodifikatorImport`    | Reads and imports XLSX content into the database  |
| `KodifikatorController`| Optional API interface for triggering actions     |
| `KodifikatorUpdateCommand` | Symfony Console command for full update      |

---

## License

This project uses publicly available government data under the  
[Creative Commons Attribution 4.0 International License (CC BY 4.0)](https://creativecommons.org/licenses/by/4.0/deed.en).
