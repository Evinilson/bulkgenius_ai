# BulkGenius AI Module Security

This document describes the security measures implemented in this module and the guidelines that must be followed for all future developments.

> [!IMPORTANT]
> All actions that modify data or perform external calls (AI APIs) **MUST** be protected by CSRF tokens and rigorous input validation.

## Implemented Measures

### 1. CSRF (Cross-Site Request Forgery) Protection
*   **AdminLite Token**: All AJAX calls (`preview`, `import_single`, `test_connection`) use the PrestaShop native token system (`Tools::getAdminTokenLite`).
*   **Mandatory Validation**: On the server side, the `checkToken()` method validates the presence and integrity of the token before processing any request. Requests without a valid token are rejected with a `403 Forbidden` error.

### 2. Data Sanitization and Validation
*   **Input Filters**: All data received via POST or files are processed:
    *   Names and descriptions pass through `strip_tags()` and `trim()`.
    *   Product references are filtered to allow only alphanumeric characters and basic separators (`preg_replace`).
    *   Prices are normalized to the floating-point decimal format.
*   **API Key Cleaning**: In the connection test, API keys are cleaned of spaces and invisible control characters that might be injected.

## Guidelines for Future Implementations

When adding new features, the developer **MUST**:
1.  **Validate the Token**: Call `$this->checkToken()` in any new AJAX processing method.
2.  **Sanitize Variables**: Never use `Tools::getValue()` directly in SQL queries or object creation without prior sanitization.
3.  **Update this File**: Whenever a new security measure is added, this `SECURITY.md` must be updated to reflect the current state of the module.

---
*Last update: April 14, 2026*
