-- Copyright (C)  Year(-Year)   Author Name             <email>
--
--  eldy
--  frederic34
--  dolibit-ut                 <dev@dolibit.de>
--

-- License
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

-- Note
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

-- socialnetworks

INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, '500px', '500px', '{socialid}', 'fa-500px', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'dailymotion', 'Dailymotion', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'diaspora', 'Diaspora', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'discord', 'Discord', '{socialid}', 'fa-discord', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'facebook', 'Facebook', 'https://www.facebook.com/{socialid}', 'fa-facebook', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'flickr', 'Flickr', '{socialid}', 'fa-flickr', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'gifycat', 'Gificat', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'giphy', 'Giphy', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'github', 'GitHub', 'https://www.github.com/{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'googleplus', 'GooglePlus', 'https://www.googleplus.com/{socialid}', 'fa-google-plus', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'instagram', 'Instagram', 'https://www.instagram.com/{socialid}', 'fa-instagram', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'linkedin', 'LinkedIn', 'https://www.linkedin.com/in/{socialid}', 'fa-linkedin', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'mastodon', 'Mastodon', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'meetup', 'Meetup', '{socialid}', 'fa-meetup', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'periscope', 'Periscope', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'pinterest', 'Pinterest', '{socialid}', 'fa-pinterest', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'quora', 'Quora', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'reddit', 'Reddit', '{socialid}', 'fa-reddit', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'slack', 'Slack', '{socialid}', 'fa-slack', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'snapchat', 'Snapchat', '{socialid}', 'fa-snapchat', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'skype', 'Skype', 'https://www.skype.com/{socialid}', 'fa-skype', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'tripadvisor', 'Tripadvisor', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'tumblr', 'Tumblr', 'https://www.tumblr.com/{socialid}', 'fa-tumblr', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'twitch', 'Twitch', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'twitter', 'Twitter', 'https://www.twitter.com/{socialid}', 'fa-twitter', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'vero', 'Vero', 'https://vero.co/{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'viadeo', 'Viadeo', 'https://fr.viadeo.com/fr/{socialid}', 'fa-viadeo', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'viber', 'Viber', '{socialid}', '', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'vimeo', 'Vimeo', '{socialid}', 'fa-vimeo', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'whatsapp', 'Whatsapp', 'https://web.whatsapp.com/send?phone={socialid}', 'fa-whatsapp', 1);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'wikipedia', 'Wikipedia', '{socialid}', 'fa-wikipedia-w', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'xing', 'Xing', '{socialid}', 'fa-xing', 0);
INSERT INTO llx_c_socialnetworks (entity, code, label, url, icon, active) VALUES ( 1, 'youtube', 'Youtube', 'https://www.youtube.com/{socialid}', 'fa-youtube', 1);
