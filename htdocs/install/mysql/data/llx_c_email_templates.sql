-- Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,enabled,active,topic,content,content_lines) VALUES (0,'banque','thirdparty','',0,null,null,'(YourSEPAMandate)',1,'$conf->societe->enabled && $conf->banque->enabled && $conf->prelevement->enabled',0,'__(YourSEPAMandate)__','__(Hello)__,<br><br>\n\n__(FindYourSEPAMandate)__ :<br>\n__MYCOMPANY_NAME__<br>\n__MYCOMPANY_FULLADDRESS__<br><br>\n__(Sincerely)__<br>\n__USER_SIGNATURE__',null);


INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,enabled,active,topic,content,content_lines,joinfiles) VALUES (0,'adherent','member','',0,null,null,'(SendingEmailOnAutoSubscription)'       ,10,'$conf->adherent->enabled',1,'[__[MAIN_INFO_SOCIETE_NOM]__] __(YourMembershipRequestWasReceived)__','__(Hello)__ __MEMBER_FULLNAME__,<br><br>\n\n__(ThisIsContentOfYourMembershipRequestWasReceived)__<br>\n<br>__ONLINE_PAYMENT_TEXT_AND_URL__<br>\n<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null, 0);
INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,enabled,active,topic,content,content_lines,joinfiles) VALUES (0,'adherent','member','',0,null,null,'(SendingEmailOnMemberValidation)'       ,20,'$conf->adherent->enabled',1,'[__[MAIN_INFO_SOCIETE_NOM]__] __(YourMembershipWasValidated)__',      '__(Hello)__ __MEMBER_FULLNAME__,<br><br>\n\n__(ThisIsContentOfYourMembershipWasValidated)__<br>__(FirstName)__ : __MEMBER_FIRSTNAME__<br>__(LastName)__ : __MEMBER_LASTNAME__<br>__(ID)__ : __MEMBER_ID__<br>\n<br>__ONLINE_PAYMENT_TEXT_AND_URL__<br>\n<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null, 0);
INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,enabled,active,topic,content,content_lines,joinfiles) VALUES (0,'adherent','member','',0,null,null,'(SendingEmailOnNewSubscription)'        ,30,'$conf->adherent->enabled',1,'[__[MAIN_INFO_SOCIETE_NOM]__] __(YourSubscriptionWasRecorded)__',     '__(Hello)__ __MEMBER_FULLNAME__,<br><br>\n\n__(ThisIsContentOfYourSubscriptionWasRecorded)__<br>\n\n<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null, 1);
INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,enabled,active,topic,content,content_lines,joinfiles) VALUES (0,'adherent','member','',0,null,null,'(SendingReminderForExpiredSubscription)',40,'$conf->adherent->enabled',1,'[__[MAIN_INFO_SOCIETE_NOM]__] __(SubscriptionReminderEmail)__',       '__(Hello)__ __MEMBER_FULLNAME__,<br><br>\n\n__(ThisIsContentOfSubscriptionReminderEmail)__<br>\n<br>__ONLINE_PAYMENT_TEXT_AND_URL__<br>\n<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null, 0);
INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,enabled,active,topic,content,content_lines,joinfiles) VALUES (0,'adherent','member','',0,null,null,'(SendingEmailOnCancelation)'            ,50,'$conf->adherent->enabled',1,'[__[MAIN_INFO_SOCIETE_NOM]__] __(YourMembershipWasCanceled)__',       '__(Hello)__ __MEMBER_FULLNAME__,<br><br>\n\n__(YourMembershipWasCanceled)__<br>\n<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null, 0);
INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,enabled,active,topic,content,content_lines,joinfiles) VALUES (0,'adherent','member','',0,null,null,'(SendingAnEMailToMember)'               ,60,'$conf->adherent->enabled',1,'[__[MAIN_INFO_SOCIETE_NOM]__] __(CardContent)__',                     '__(Hello)__,<br><br>\n\n__(ThisIsContentOfYourCard)__<br>\n__(ID)__ : __ID__<br>\n__(Civiliyty)__ : __MEMBER_CIVILITY__<br>\n__(Firstname)__ : __MEMBER_FIRSTNAME__<br>\n__(Lastname)__ : __MEMBER_LASTNAME__<br>\n__(Fullname)__ : __MEMBER_FULLNAME__<br>\n__(Company)__ : __MEMBER_COMPANY__<br>\n__(Address)__ : __MEMBER_ADDRESS__<br>\n__(Zip)__ : __MEMBER_ZIP__<br>\n__(Town)__ : __MEMBER_TOWN__<br>\n__(Country)__ : __MEMBER_COUNTRY__<br>\n__(Email)__ : __MEMBER_EMAIL__<br>\n__(Birthday)__ : __MEMBER_BIRTH__<br>\n__(Photo)__ : __MEMBER_PHOTO__<br>\n__(Login)__ : __MEMBER_LOGIN__<br>\n__(Password)__ : __MEMBER_PASSWORD__<br>\n__(Phone)__ : __MEMBER_PHONE__<br>\n__(PhonePerso)__ : __MEMBER_PHONEPRO__<br>\n__(PhoneMobile)__ : __MEMBER_PHONEMOBILE__<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null, 0);

INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,enabled,active,topic,content,content_lines,joinfiles) VALUES (0,'recruitment','recruitmentcandidature_send','',0,null,null,'(AnswerCandidature)'                    ,100,'$conf->recruitment->enabled',1,'[__[MAIN_INFO_SOCIETE_NOM]__] __(YourCandidature)__',       '__(Hello)__ __CANDIDATE_FULLNAME__,<br><br>\n\n__(YourCandidatureAnswerMessage)__<br>__ONLINE_INTERVIEW_SCHEDULER_TEXT_AND_URL__\n<br><br>\n__(Sincerely)__<br>__USER_SIGNATURE__',null, 0);

