#!/usr/bin/env python
#
# Glasnost
# By: Odile Bénassy <obenassy@entrouvert.com>
#     Thierry Dulieu <tdulieu@easter-eggs.com>
#     Frédéric Péters <fpeters@theridion.com>
#     Benjamin Poussin <poussin@codelutin.com>
#     Emmanuel Raviart <eraviart@entrouvert.com>
#     Emmanuel Saracco <esaracco@easter-eggs.com>
#
# Copyright (C) 2000, 2001 Easter-eggs & Emmanuel Raviart
# Copyright (C) 2002 Odile Bénassy, Code Lutin, Thierry Dulieu, Easter-eggs,
#     Entr'ouvert, Frédéric Péters, Benjamin Poussin, Emmanuel Raviart,
#     Emmanuel Saracco & Théridion
# Copyright (C) 2003 Odile Bénassy, Code Lutin, Thierry Dulieu, Easter-eggs,
#     Entr'ouvert, Ouvaton, Frédéric Péters, Benjamin Poussin, Rodolphe
#     Quiédeville, Emmanuel Raviart, Emmanuel Saracco, Théridion & Vecam
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.


__doc__ = """Sample showing how to handle people with XML-RPC"""

__version__ = '$Revision$'[11:-2]


import xmlrpclib # Requires Python >= 2.2.


# Every calls to a Glasnost server is handled by a Glasnost XML-RPC gateway.
glasnostServerName = 'localhost' ### YOU MAY NEED TO CHANGE THIS!!!
glasnostGatewayPort = 8001

# The login & password of a Glasnost user who has the rights to add people to
# the server.
login = 'login' ### CHANGE THIS!!!
password = 'password' ### CHANGE THIS!!!

# Each Glasnost server is uniquely identified by its server id.
authenticationServerId = 'glasnost://%s/authentication' % glasnostServerName
peopleServerId = 'glasnost://%s/people' % glasnostServerName

# This sample application doesn't need an application token.
applicationToken = ''

# First, establish a connection to the gateway.
gateway = xmlrpclib.ServerProxy('http://%s:%d' % (
    glasnostServerName, glasnostGatewayPort))

# Call the authentication server to give him your login & password and to
# receive a user id and token.
userId, userToken = gateway.callGateway(
    authenticationServerId,
    'getUserIdAndToken',
    [authenticationServerId, applicationToken, login, password])
print 'Login = %s' % login
print 'User ID = %s' % userId
print 'User Token = %s' % userToken

# Create a new person.
# Note: The attributes of people are described in shared/common/PeopleCommon.py
person = {
    # Don't touch the next two lines.
    '__thingCategory__': 'object',
    '__thingName__': 'Person',

    'firstName': 'John',
    'lastName': 'Doe',
    'login': 'jdoe',
    'email': 'root@localhost', ### CHANGE THIS!!!
    }

# Call the method addObject of the people server.
# Note: The available functions of the people server are defined in the class
# PeopleServer, which is defined in servers/PeopleServer/PeopleServer.py.
# The class PeopleServer inherits from the class ObjectsServer, which is
# defined in shared/server/ObjectsServer.py
personId = gateway.callGateway(
    peopleServerId,
    'addObject' ,
    [peopleServerId, applicationToken, userToken, person])
print 'Person created with id = %s' % personId

# Give a person id and get its infos.
person = gateway.callGateway(
    peopleServerId,
    'getObject' ,
    [peopleServerId, applicationToken, userToken, personId])
print 'Got a new person = %s' % person

# Change the nickname of that person.
person['nickname'] = 'jd'
gateway.callGateway(
    peopleServerId,
    'modifyObject' ,
    [peopleServerId, applicationToken, userToken, person])

# Get the new infos of the person.
person = gateway.callGateway(
    peopleServerId,
    'getObject' ,
    [peopleServerId, applicationToken, userToken, personId])
print 'Got a modified person = %s' % person

# Remove the person.
gateway.callGateway(
    peopleServerId,
    'deleteObject' ,
    [peopleServerId, applicationToken, userToken, personId])
print 'Person deleted'
