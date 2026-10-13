#!/usr/bin/env python3
# Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
# SPDX-License-Identifier: GPL-3.0-or-later
"""Test project list fragments using native PHP and an in-memory SQLite fixture.

Run: python test/other/test_project_list_columns.py [htdocs-relative-list-path ...]
Requires PHP on PATH. This is an isolated regression test, not an instance test.
"""
from pathlib import Path
import json
import sqlite3
import subprocess
import sys
ROOT = Path(__file__).resolve().parents[2]
CONFIG = [('fourn/commande/list.php', 'cf.fk_projet', 'p', 'cf.fk_projet', 'p.title', 'explicit'),
 ('fourn/facture/list.php', 'f.fk_projet', 'p', 'p.ref', 'p.title', 'explicit'),
 ('supplier_proposal/list.php', 'sp.fk_projet', 'p', 'sp.fk_projet', 'p.title', 'explicit'),
 ('compta/sociales/list.php', 'cs.fk_projet', 'p', 'p.ref', 'p.title', 'explicit'),
 ('comm/propal/list.php', 'p.fk_projet', 'pr', 'pr.ref', 'pr.title', 'explicit'),
 ('commande/list.php', 'c.fk_projet', 'p', 'p.ref', 'p.title', 'explicit'),
 ('commande/list_det.php', 'c.fk_projet', 'p', 'p.ref', 'p.title', 'explicit'),
 ('compta/facture/list.php', 'f.fk_projet', 'p', 'p.ref', 'p.title', 'explicit'),
 ('product/stock/movement_list.php', 'm.fk_projet', None, 'm.fk_projet', 'projectlist.title', 'explicit'),
 ('compta/bank/various_payment/list.php', 'v.fk_projet', None, 'project', 'projectlist.title', 'explicit'),
 ('contrat/list.php', 'c.fk_projet', None, None, None, 'new'),
 ('expedition/list.php', 'e.fk_projet', None, None, None, 'new'),
 ('reception/list.php', 'e.fk_projet', None, None, None, 'new'),
 ('fichinter/list.php', 'f.fk_projet', None, None, None, 'new'),
 ('product/list.php', 'p.fk_project', None, None, None, 'new'),
 ('compta/facture/invoicetemplate_list.php', 'f.fk_projet', None, None, None, 'new'),
 ('fourn/facture/list-rec.php', 'f.fk_projet', None, None, None, 'new'),
 ('comm/action/list.php', 'a.fk_project', None, None, None, 'new'),
 ('loan/list.php', 'l.fk_projet', None, None, None, 'loan'),
 ('salaries/list.php', 's.fk_projet', None, 's.fk_projet', 'projectlist.title', 'new'),
 ('ticket/list.php', 't.fk_project', None, 't.fk_project', 'projectlist.title', 'generic'),
 ('mrp/mo_list.php', 't.fk_project', None, 't.fk_project', 'projectlist.title', 'generic'),
 ('product/stock/list.php', 't.fk_project', None, 't.fk_project', 'projectlist.title', 'generic'),
 ('recruitment/recruitmentjobposition_list.php',
  't.fk_project',
  None,
  't.fk_project',
  'projectlist.title',
  'generic'),
 ('bookcal/calendar_list.php', 't.fk_project', None, 't.fk_project', 'projectlist.title', 'generic'),
 ('product/stock/stocktransfer/stocktransfer_list.php',
  't.fk_project',
  None,
  't.fk_project',
  'projectlist.title',
  'generic'),
 ('eventorganization/conferenceorbooth_list.php',
  't.fk_project',
  None,
  't.fk_project',
  'projectlist.title',
  'generic'),
 ('eventorganization/conferenceorboothattendee_list.php',
  't.fk_project',
  None,
  't.fk_project',
  'projectlist.title',
  'generic'),
 ('salaries/payments.php', 's.fk_projet', None, None, None, 'new'),
 ('accountancy/expensereport/list.php', 'erd.fk_projet', None, None, None, 'new'),
 ('fourn/product/list.php', 'p.fk_project', None, None, None, 'new'),
 ('don/list.php', 'd.fk_projet', 'p', 'd.fk_projet', 'p.title', 'don'),
 ('comm/mailing/list.php', 'm.fk_project', 'pr', 'm.fk_project', 'pr.title', 'mailing')]

db=sqlite3.connect(':memory:');db.row_factory=sqlite3.Row
db.executescript('''
CREATE TABLE fixture_projet(rowid INTEGER PRIMARY KEY,ref TEXT,title TEXT,public INTEGER,entity INTEGER,fk_soc INTEGER,fk_statut INTEGER);
CREATE TABLE fixture_c_type_contact(rowid INTEGER,code TEXT,element TEXT,source TEXT);
CREATE TABLE fixture_element_contact(rowid INTEGER,element_id INTEGER,fk_c_type_contact INTEGER,fk_socpeople INTEGER);
CREATE TABLE fixture_objects(rowid INTEGER,fk_project INTEGER,fk_projet INTEGER);
INSERT INTO fixture_c_type_contact VALUES(1,'PROJECTLEADER','project','internal');
INSERT INTO fixture_element_contact VALUES(1,2,1,7);
''')
projects=[(1,'P1',"A & B <img src=x onerror=alert(1)> 'été'",1,1,9,1),(2,'P2','Assigned private',0,1,9,1),(3,'SECRET','Inaccessible private',0,1,999,1),(4,'SHARED','Shared entity',1,2,9,1),(5,'OTHER','Unshared entity',1,3,9,1),(6,'EXTERNAL','Other third party',1,1,999,1)]
db.executemany('INSERT INTO fixture_projet VALUES(?,?,?,?,?,?,?)',projects)
db.executemany('INSERT INTO fixture_objects VALUES(?,?,?)',[(i,i,i) for i in range(1,7)]+[(7,None,None),(8,99,99)])
scenarios=[('restricted',dict(enabled=True,read=True,all=False,entities='1'),{1,2,6}),('all',dict(enabled=True,read=True,all=True,entities='1'),{1,2,3,6}),('shared',dict(enabled=True,read=True,all=False,entities='1,2'),{1,2,4,6}),('external',dict(enabled=True,read=True,all=False,socid=9,entities='1,2'),{1,2,4}),('denied',dict(enabled=True,read=False,all=True,entities='1'),set()),('disabled',dict(enabled=False,read=True,all=True,entities='1'),set())]

def call(data):
    r=subprocess.run(['php',str(Path(__file__).with_name('project_list_columns_fixture.php'))],input=json.dumps(data),capture_output=True,text=True,encoding='utf-8')
    if r.returncode or r.stderr: raise AssertionError((data['file'],r.returncode,r.stderr[:1400],r.stdout[:300]))
    return json.loads(r.stdout)

results=[];checks=0
configs=CONFIG+[('bookcal/booking_list.php','ac.fk_project',None,'projectlist.ref','projectlist.title','new')]
for f,relation,alias,refkey,titlekey,style in configs:
    if len(sys.argv)>1 and f not in sys.argv[1:]:continue
    refkey=refkey or 'projectlist.ref';titlekey=titlekey or 'projectlist.title'
    base=dict(root=str(ROOT),file=f,refkey=refkey,titlekey=titlekey,relation_alias=relation.split('.')[0])
    for name,scope,expected in scenarios:
        cfg=base|scope|dict(stage='scope')
        probe=call(cfg)
        assert probe['metadata'][titlekey]['checked']=='0',(f,name,'label enabled by default')
        assert probe['metadata'][titlekey]['enabled']==('1' if scope['enabled'] and scope['read'] else '0')
        if probe['queries']:
            authorized=[dict(r) for r in db.execute(probe['queries'][-1])]
        else:authorized=[]
        assert {r['rowid'] for r in authorized}==expected,(f,name,authorized)
        row=dict(projectlist_id=1 if 1 in expected else None,projectlist_ref=projects[0][1],projectlist_title=projects[0][2],projectlist_public=1,projectlist_status=1)
        cfg=base|scope|dict(projects=authorized,row=row,ajax=1)
        actual=call(cfg);rows=[dict(r) for r in db.execute(actual['sql']+' ORDER BY object_id')]
        assert len(rows)==8,(f,name,'lost rows')
        visible={r['projectlist_id'] for r in rows if r['projectlist_id'] is not None}
        assert visible==expected,(f,name,'scope leak',visible)
        if expected:
            assert 'classforajaxtooltip' in actual['html']['title'] and 'id=1' in actual['html']['title'],(f,name)
            assert '<img src=x' not in actual['html']['title'] and '&lt;img' in actual['html']['title'],(f,name,'unsafe title')
        else: assert '<a' not in actual['html']['title'] and '<a' not in actual['html']['ref'],(f,name,'link leak')
        checks+=6
    scope=scenarios[0][1]
    probe=call(base|scope|dict(stage='scope'));authorized=[dict(r) for r in db.execute(probe['queries'][-1])]
    row=dict(projectlist_id=1,projectlist_ref=projects[0][1],projectlist_title=projects[0][2],projectlist_public=1,projectlist_status=1)
    for label,opts in [('title only',dict(refchecked=0,titlechecked=1,ajax=0)),('ref only',dict(refchecked=1,titlechecked=0,ajax=1)),('none',dict(refchecked=0,titlechecked=0,ajax=1)),('missing project',dict(row=row|dict(projectlist_id=None))),('combined filter',dict(search_ref='P1',search_title="'été'")),('inaccessible filter',dict(search_ref='SECRET')),('SQL injection',dict(search_title="' OR 1=1 --"))]:
        cfg=base|scope|dict(projects=authorized,row=row,ajax=1)|opts
        actual=call(cfg)
        filtered=list(db.execute(actual['sql']))
        if label=='title only': assert not actual['html']['ref'] and 'classfortooltip' in actual['html']['title'] and 'A &amp; B' in actual['html']['title']
        if label=='ref only': assert actual['html']['ref'] and not actual['html']['title']
        if label=='none': assert not any(actual['html'].values())
        if label=='missing project': assert '<a' not in ''.join(actual['html'].values())
        if label=='combined filter': assert len(filtered)==1 and filtered[0]['object_id']==1
        if label in ('inaccessible filter','SQL injection'): assert len(filtered)==0
        checks+=1
    results.append(dict(file='htdocs/'+f,status='pass',scenarios=13));print('PASS',f,flush=True)
# Results stay on stdout; this test never writes repository or instance data.
print('PASS',checks,'checks across',len(results),'lists')
