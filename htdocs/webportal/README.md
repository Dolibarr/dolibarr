# Web Portal Module for Dolibarr

The Web Portal module provides a secure and customizable client interface, allowing third parties (customers, suppliers, members, etc.) to access their information and documents directly from a public web interface.

## New Features Offered

This contribution adds two new document management areas to enhance the portal.

### 1. "My Documents" Area (by Third Party)

* A new page allows a logged-in user to view and download documents shared with them personally.
* **Link to the Third Party's EDM:** Files are managed simply by adding them to the "Attached Files" tab of the Third Party's file in the Dolibarr administration interface. Any file added is instantly visible on the client's portal. * **Folder naming:** The system uses the third-party **reference** (e.g., `CU25-0001`) to locate the directory, in accordance with Dolibarr standards.

### 2. "Shared Documents" area (global)

* A second page has been added to display a list of documents common to **all** portal users (e.g., brochures, general terms and conditions of sale, etc.).
* **Centralized management:** Files are placed in a single directory within the ECM/DMS module. The name of this directory is configurable by the administrator.

## Required configuration

All configuration is done from the WebPortal module settings page (**Home > Configuration > Modules > WebPortal**).

1. **Enable the "My Documents" page**: Enable the corresponding option using the Yes/No switch.
2. **Enable the "Shared Documents" page**: Enable the corresponding option. This feature requires the **ECM/GED** module to be enabled.
3. **Choose the shared folder**: Enter the name of the shared documents directory (e.g., `PublicDocuments`) in the text field provided.

## License

This project is distributed under the GNU General Public License v3.0, like the original Dolibarr project.
