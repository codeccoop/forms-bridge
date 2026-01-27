# Grist Addon for Forms Bridge

This addon integrates Forms Bridge with Grist using its REST API.

## Features

- Connect WordPress forms to Grist databases
- Map form fields to Grist table columns
- Support for all Grist column types (Text, Numeric, Bool, Date, DateTime, Choice, Ref, RefList)
- Automatic type mapping between WordPress and Grist

## Requirements

- Forms Bridge plugin installed and activated
- Grist account with API access
- Grist API token

## Setup

1. **Install the addon**: Place the `grist` folder in `forms-bridge/addons/`
2. **Enable the addon**: Go to Forms Bridge settings and enable the Grist addon
3. **Configure backend**: 
   - Go to Forms Bridge → Backends
   - Add a new backend with your Grist API URL (e.g., `https://docs.getgrist.com`)
   - Add your Grist API token as the access token
4. **Create a bridge**:
   - Go to Forms Bridge → Bridges
   - Add a new bridge with the Grist addon
   - Select your form and the Grist backend
   - Set the endpoint to your Grist table API (e.g., `/api/tables/{tableId}/records`)
   - Map your form fields to Grist columns

## API Endpoints

Common Grist API endpoints:
- `/api/docs` - API documentation
- `/api/tables` - List tables
- `/api/tables/{tableId}` - Get table info
- `/api/tables/{tableId}/records` - Get/add records
- `/api/tables/{tableId}/schema` - Get table schema

## Field Mapping

Grist column types are automatically mapped to appropriate form field types:

| Grist Type | Form Type |
|------------|-----------|
| Text       | text      |
| Numeric    | number    |
| Bool       | boolean   |
| Date       | string    |
| DateTime   | string    |
| Choice     | string    |
| Ref        | string    |
| RefList    | array     |

## Troubleshooting

- **Connection issues**: Verify your Grist API URL and token are correct
- **Field mapping errors**: Check that your form fields match the Grist column types
- **Permission errors**: Ensure your API token has write access to the target table

## Support

For support, please open an issue on the Forms Bridge GitHub repository.
