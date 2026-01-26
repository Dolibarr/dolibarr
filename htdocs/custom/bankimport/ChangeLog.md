# Change Log
All notable changes to this project will be documented in this file.

## Release 2.9
- NEW : Add date-proximity matching for improved transaction reconciliation - *2026-01-26* - 2.9.0
- FIX : Changed bank transaction sort order to chronological (ASC) for correct matching - *2026-01-26* - 2.9.0

## Release 2.8
- FIX : Compat V22 - *2025-10-02* - 2.8.3
- FIX remove echoing select_comptes *2025-08-12* - 2.8.2
- FIX : DA026669 Depends - *2025-06-26* - 2.8.1
- NEW : add  german langs - *17/06/2025* - 2.8.0  

## Release 2.7
- FIX : Compat v21 *07/01/2025* - 2.7.1
- FIX : Compat v20
  Changed Dolibarr compatibility range to 16 min - 20 max - *04/08/2024* - 2.7.0
 
## Release 2.6
- FIX : Ticket DA025234 - Fix undefined variable in conf.php - *11/07/2024* - 2.6.3
- FIX : Ticket DA024988 - bankline date was convert twice - *24/05/2024* - 2.6.2
- FIX : Update include path for main.inc.php in setup page - *19/04/2024* - 2.6.1
- NEW : TechATM + Page About + logo + fix visu tpl - *07/12/2023* - 2.6.0
- NEW : COMPATV19 - *07/12/2023* - 2.5.0  

## Release 2.4

- FIX : PHP8.2 transtypage *28/06/2023* - 2.4.1
- NEW : Hooks *03/05/2023* - 2.4.0
- FIX (contrib by mkdgs): regex for parsing CSV format options - *29/07/2022* - 2.3.1
- NEW (contrib by caos30): Spanish translation - *29/07/2022* - 2.3.0

## Release 2.2

- FIX: CompatibilityV17 - *12/01/2023* - 2.2.9
- FIX: CompatibilityV16 - Change family - *30/06/2022* - 2.2.8
- FIX : Import keep saving PRE on new paiement but user doesn't selected this paiement   *24/01/2022* 2.2.7
- FIX : V12 Compatibility card url for paiement *24/01/2022* 2.2.6
- FIX : Compatibility V13 - newToken() replaces $_SESSION['newtoken'] and add token renewal - *17/12/2021* - 2.2.5
- FIX : Uniformize module descriptor's editor, editor_url and family fields * 08/06/2021* - 2.2.4
- FIX : V13 Compatibility societe_id / socid *10/05/2021* 2.2.3
- FIX : V13 Compatibility No Token Renewal *10/05/2021* - 2.2.2
- FIX : V13 Compatibility after num_paiement property changed to num_payment *03/03/2021* - 2.2.1

## Release 1.0

- NEW : Automatic bank statement reconciliation *16/09/2013* - 1.0
- NEW : Automatic bank transaction creation when missing *16/09/2013* - 1.0
- NEW : Date format, file separator and file mapping can be defined in setup *16/09/2013* - 1.0

