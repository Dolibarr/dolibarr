# Pre-Opportunity Dolibarr Module

Currently in Dolibarr there is no separate place for sales team to keep the details of records which are suspects but they are not yet a Customer or a Prospect. This is the list where sales team fishes to find opportunity by making cold calls, emails etc. Also Sales team will not like to clutter their list of Customer & Prospects with this list or dump of suspects, hence they prefer to keep the list of suspects separately. To resolve this practical challenege faced by Sales team we have come up with this **Pre-Opportunity** module.

This module for Dolibarr is designed to enhance your Sales Force Automation process by allowing you to track, organize, and convert suspects/pre-opportunities into opportunities more effectively. With this module, you can store the details of various suspects/pre-opportunities gathered from different sources, track their status, assign sales personnel, document conversations, and convert them into an opportunity at appropriate time. It also allows for adding custom fields, ensuring flexibility and adaptability to your business needs.

## Features

- **Pre-Opportunity Management**: Store detailed information about each suspect/pre-opportunity, including the source, the current status (Open/Closed), and custom fields as needed.
  
- **Assign Salesperson**: Assign to specific sales personnel to ensure proper follow-up and accountability.

- **Events for Conversations**: Track conversations and interactions with record by adding events. This helps in maintaining a complete history of all related communication.

- **Lead Conversion**: 
  - A "Lead Conversion" button is available to easily convert into an opportunity.
  - Upon clicking the **Lead Conversion** button, the following happens automatically:
    1. A third party is created based on the record's information.
    2. Contacts associated with the third party are created.
    3. A new project/lead creation page opens, allowing you to add necessary project details.
    4. Once the required fields are filled in and saved, a new project/lead is created.
  
- **Automatic Linking**: After lead conversion, the newly created third party, contacts, and project are automatically linked to the pre-opportunity. Additionally, the status of the original Pre-Opportunity will be updated to **Closed**.

 - **Dynamic Source and Follow-up Status**: 
  - The **Source** (e.g., marketing campaigns, referrals, etc.) and **Follow-up/Sales Status** (e.g., in progress, won, lost) are dynamic and can be customized via the **Setup** -> **Dictionary** section. This allows businesses to tailor these fields based on their processes.
  
  - Add, remove, or modify sources and follow-up status as your business needs evolve.

## Usage

### Adding a New Pre-Opportunity

1. Navigate to the **Pre-Opportunity** section from the main menu.
2. Click on the **New Pre-Opportunity**.
3. Fill out the details, such as name, source, and any custom fields you've added.
4. Assign the Pre-Opportunity to a salesperson if necessary.
5. Save the Pre-Opportunity.

### Managing Conversations

1. Open an existing Pre-Opportunity.
2. Click on the **Events** section to add details of your conversations or meetings.
3. Save the event for future reference.

### Importing Pre-Opportunity

1. Navigate to the **Tools** -> **New Export**.
2. Select the **Pre-Opportunity** option from the list.
3. Upload a CSV or Excel file containing Pre-Opportunity data.
4. The system will import the Pre-Opportunities into the module, provided the CSV or Excel format is valid.

### Exporting Pre-Opportunity

1. Navigate to the **Tools** -> **New Export**.
2. Select the **Pre-Opportunity** option from the list.
3. The system will generate a CSV or Excel file with all the current Pre-Opportunities, which you can download.

### Sending Mass Emails

1. Go to the **Tools** -> **New emailing**.
2. Add the new Email.
3. Navigate to the **Recipients** From **EMailing card**.
4. Select records from the list that you want to include as participants in the email campaign.
5. The system will send the email to all the selected participants.

### Lead Conversion

1. When a Pre-Opportunity is ready to be converted into a project, open the lead's details page.
2. Click the **Lead Conversion** button.
3. The system will automatically create the related third party and contacts.
4. A new project/lead creation page will open—fill out the necessary details and save.
5. The system will mark the original Pre-Opportunity as **Closed**, and link the new third party, contacts, and project with the pre-opportunity.

### Customizing Sources and Contact Types

1. Go to **Setup** -> **Dictionary**.
2. In the **Dictionary** section, you can add, edit, or delete:
   - **Sources**: Define where Pre-Opportunity are coming from (e.g., social media, email campaigns, referrals).
   - **Contact Type**: Customize Contact Type according to your workflow.


## Custom Fields

You can add custom fields in the Pre-Opportunity module to capture additional data. To add custom fields:

1. Go to the **Setup** -> **Pre-Opportunity** section.
2. Add new fields as needed, which will appear on the Pre-Opportunity creation form.



## License

GPLv3 or (at your option) any later version. See file COPYING for more information.

## Support

For any issues or feature requests, please contact us at [support@accellier.com].
