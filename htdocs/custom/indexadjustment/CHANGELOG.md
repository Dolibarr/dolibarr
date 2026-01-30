# Changelog

All notable changes to the IndexAdjustment module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2025-01-30

### Fixed

- Add unique constraint on (ref, entity) to prevent duplicate adjustment references
- Store original VAT rate (tva_tx) in adjustment lines for proper rollback
- Rollback now restores original VAT rate instead of setting it to 0

## [1.0.0] - 2025-01-19

### Added

- Initial release
- Core business objects: IndexAdjustment, IndexAdjustmentLine
- IndexAdjustmentCalculator for price calculations
- IndexAdjustmentService for business logic
- Database tables: llx_indexadjustment, llx_indexadjustment_line
- Admin setup page with configuration options
- English (en_US) and German (de_DE) translations
- Unit tests for Calculator (31 tests)
- Service tests for business logic
- Permissions: read, write, execute, rollback, delete
- Contract card tab for adjustment history
- Menu integration under Commercial → Contracts
- ActionComm event creation per contract
- Rollback capability with configurable time window
- TDD approach: tests written before implementation

### Technical Details

- Module ID: 510100
- Dependencies: Contract module (modContrat)
- Uses Contrat::updateline() for native price updates
- Only adjusts active service lines (statut=4)
- Reference format: IA-YYYY-NNNN (e.g., IA-2024-0001)
