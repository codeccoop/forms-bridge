# Formidable Integration Tests

## Overview
This document describes the test structure for the Formidable Forms integration with the Forms Bridge plugin.

## Files Created

### 1. Test File
**Location:** `tests/integrations/test-formidable.php`

This file contains the main test class `FormidableTest` that extends `BaseIntegrationTest` and implements the required methods for testing the Formidable Forms integration.

**Key Components:**
- `NAME` constant set to `'formidable'`
- Implementation of required methods:
  - `get_forms()` - Retrieves published forms using `FrmForm::get_published_forms()`
  - `add_form($config)` - Creates a new form with fields
  - `delete_form($form)` - Deletes a form using `FrmForm::destroy()`

**Test Methods:**
1. `test_job_position_form_serialization()` - Tests form field serialization
2. `test_job_position_form_submission_serialization()` - Tests submission data serialization
3. `test_contact_form_serialization()` - Tests complex form with various field types
4. `test_contact_form_submission_serialization()` - Tests complex submission data
5. `test_form_templates()` - Tests form creation from templates

### 2. Test Data Files
**Location:** `tests/data/formidable/`

#### Form Data Files:
- `job-position-form.php.txt` - Serialized Formidable form object for "Job position" form
- `contact-form.php.txt` - Serialized Formidable form object for "Contact Form"

#### Submission Data Files:
- `job-position-submission.php.txt` - Serialized submission data for job position form
- `contact-submission.php.txt` - Serialized submission data for contact form

## Test Structure

The tests follow the same pattern as other integration tests in the codebase:

1. **Form Serialization Tests**: Verify that form fields are correctly serialized with proper types, labels, and options
2. **Submission Serialization Tests**: Verify that submission data is correctly extracted and formatted
3. **Template Tests**: Verify that forms can be created from template definitions

## Running the Tests

To run the formidable integration tests:

```bash
# First, set up the WordPress test environment
./bin/install-wp-tests.sh db_name db_user db_password db_host wp_version

# Then run the tests
./vendor/bin/phpunit tests/integrations/test-formidable.php
```

## Test Data Format

The test data files contain serialized PHP objects that represent:

### Form Objects:
- Form ID, name, description, form_key, status
- Form metadata and configuration

### Submission Objects:
- Submission ID, form_id, timestamps
- Meta data containing field values
- Each field value includes field_id, meta_value, and timestamps

## Integration with Base Test Class

The `FormidableTest` class extends `BaseIntegrationTest` which provides:
- Common test utilities and assertions
- Form and submission serialization methods
- Template loading and testing infrastructure
- Setup and teardown methods for test isolation

## Key Assertions

The tests use the `assertField()` helper method from the base class to verify:
- Field type and basetype
- Field schema (data type)
- Required status
- File upload status
- Multi-value status
- Conditional logic status
- Field options and labels

## Future Enhancements

Potential areas for additional testing:
- Repeater fields
- Embedded forms
- Conditional logic
- File upload handling
- Complex field types (address, credit card, etc.)
