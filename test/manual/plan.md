
This files contains the test plan to run manually to validate critical feature about takepos and that 
we can't validate automatically with unit tests.

***** 0)
Load the initdemo database.
Delete all data from 2025-01-01.


***** 1) 
Set date to 2025-01-15
Enable the Unalterable Log


***** 2)
Set date to 2025-01-16
Create n sales in cash from TakePOS
Create n sales in cash from Backoffice
=> Cheack amount is ok
Print ticket
=> Check unaterable log that amount is ok and that event appears.
Send ticket by email.
=> Check unaterable log that amount is ok and that event appears.


***** 3)
Set date to 2025-03-05
Create the cash control for the past day and the month January
Check you can create cash control for February

- Create n sales with cash from Takepos with full payment
- Create n sales with cheque from Takepos with full payment
- Close the cash control for the day
- Try to make another sale for the same day => Should fails


***** 4)
Set date to 2025-31-01
- Empty the cashdesk, move money to a financial bank account


***** 5)
Set date to 2025-16-01


***** 6)
Set date to 2025-16-01


***** 7)
Set date to 2025-16-01
