
This files contains the test plan to run manually to validate critical feature about takepos and that 
we can't validate automatically with unit tests.



***** 0)
Load the initdemo database.
Delete all data from 2025-01-01.


***** 1 -  Test init module Unalterable Log
Set date to 2025-01-15
Enable the Unalterable Log


***** 2 - Create sales, print and send email and check unalterbale logs
Set date to 2025-01-16
Create n sales in cash from TakePOS
Create n sales in cash from Backoffice
=> Cheack amount is ok
Print ticket
=> Check mentions on tickets.
=> Check unaterable log that amount is ok and that event appears.
Send ticket by email.
=> Check unaterable log that amount is ok and that event appears.


***** 3 - Test you can't create sale on a closed cash control
Set date to 2025-02-05
Create the cash control for the past day and the month January
Check you can create cash control for February

- Create n sales with cash from Takepos with full payment
- Create n sales with cheque from Takepos with full payment
- Close the cash control for the day
- Close the cash control for the month
=> Check the perpetual amount in cash control and into the blocked log is ok and cumulate all pas events payments.
- Try to make another sale for the same day => Should fails
- Reopen the cash controls for the day/month
- Try to make another sale for the same day => Should succeed
- Reclose the cash control


***** 4 - Test credit note on too high invoice amount (higher that amount received)
Set date to 2025-03-05
Create the cash control for the past day and the month January
Check you can create cash control for February

- Create a sales with payent minus 0.01 euros
- Go on the backoffice and create a credit not for the remain to pay.
- Convert the credit not and consume it into the not closed takepos invoice.
=> Check that takepos invoice is now closed
=> Check in unalterable log that sum of amounts paid = sum of amounts invoiced


***** 5 - Test delete of payment
Set date to 2025-03-15
- Create a sale with payment by card.
- It was an error, payment is by cash, so go into backoffice, reopen invoice, delete payment, add another one.
=> Go to unalerable log and check sum of payments
- Then export archive of march
- Control the archive file


***** 6)
Set date to 2025-04-05
- Redo a cash control


***** 7)
Set date to 2025-04-15
- Create a sale and do a full credit note
