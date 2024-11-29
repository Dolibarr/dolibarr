//---------------------------------------------------------------------------
// UsedPort.cpp
//---------------------------------------------------------------------------
// Tested with :
// GCC CYGWIN  3.4.4 (May need cygwin1.dll, depending on functions used)
// GCC MINGW   3.4.5
// Not tested with:
// VC++        4.0.0	
// GCC Linux   3.4.4
//---------------------------------------------------------------------------
// 06/09/09	1.0		Laurent Destailleur	   Creation
//---------------------------------------------------------------------------
#define PROG	 "UsedPort"
#define VERSION "1.0"

// If GNU GCC CYGWIN: _WIN32 to defined manually,  __GNUC__ is defined,     _MSC_VER not defined
// If GNU GCC MINGW:  _WIN32 automaticaly defined, __GNUC__ is defined,     _MSC_VER not defined
// If VC:             _WIN32 automaticaly defined, __GNUC__ is not defined, _MSC_VER defined

// If on Windows and Cygwin, we can use _WIN32 WSA pre function, if we want or not.
//#define _WIN32

#include <string.h>
#include <stdio.h>
#include <stdlib.h>

#ifndef _WIN32
// Pour Unix
#include <sys/socket.h>
#include <netinet/in.h>
#include <netdb.h>
#include <unistd.h>
#define SOCKET int			// Non defini sous Unix
#define INVALID_SOCKET (SOCKET)(~0)	// Non defini sous Unix
#define SOCKADDR_IN struct sockaddr_in	// Non defini sous Unix
#define LPHOSTENT struct hostent *	// Non defini sous Unix
#define MAXHOSTNAMELEN 256
#endif

#ifdef _WIN32
#ifdef _MSC_VER
// Pour VC++
#include <direct.h>
#include <winsock.h>
#define MAXHOSTNAMELEN 256
#endif
#ifdef __GNUC__
#define MAXHOSTNAMELEN 256
// Pour GCC WIN32 CYGWIN
#include <winsock.h>
// Pour GCC WIN32 EGCS
//#include <base.h>
//#include <defines.h>
//#include <structures.h>
//#include <sockets.h>
//#include <functions.h>
#endif
#endif



#define MAX_ENTRIES 10					// SUBJECT, HOST, USER, PASSWD
#define MAX_MAILS 	100					// Nb max of mails managed
#define SIZE_RECEIVE_BUFFER 4096		// Ko
#define SIZE_BLOCK_FILELIST 256			// Size of block used to extend by alloc/realloc files list string

#define UNKNOWN_ERROR 1
#define BAD_ACTION 2
#define BAD_PASSWORD  3
#define FAILED_TO_START_SOCKETS 4
#define FAILED_TO_RESOLVE_HOST 5
#define FAILED_TO_OBTAIN_SOCKET_HANDLE 6
#define FAILED_TO_CONNECT 7
#define FAILED_TO_SEND 8
#define FAILED_TO_RECEIVE 9
#define SERVER_ERROR 10
#define FAILED_TO_GET_HOSTNAME 11
#define OUT_OF_MEMORY 12
#define FAILED_TO_PARSE_CGI 13
#define NB_OF_MAILS 14
#define SIZE_OF_MAILS 15
#define BAD_USER 16
#define BAD_HOST 17
#define TO_MANY_MAILS 18
#define MAIL_UNKNOWN 19
#define BAD_CGIEXE 20
#define MAIL_DELETED 21
#define BAD_FORMAT_MAIL 22

#define SIZE_TEXT 23
#define FROM_TEXT 24
#define SUBJECT_TEXT 25
#define ATTACHED_FILE_TEXT 26
#define NONE_TEXT 27
#define DELETE_TEXT 28


// Types
typedef struct {
	char *mail;
	unsigned long int size;
	char *received_time;
	char *subject;
	char *return_path;
	char *from;
	char *status;
	char *mime_version;
	char *files;
} mailentry;

typedef struct {
    char *name;
    char *val;
} entry;


// Variables
entry entries[MAX_ENTRIES];				// Tab of CGI entries		First=0
mailentry tabmails[MAX_MAILS+1];		// Tab of mails entries		First=1


int  iRet;
int  iNbUnread;						// Nb of unread mails
unsigned long int lSizeUnread;		// Size of all unread mails
int  Port=0;					
char Host[MAXHOSTNAMELEN]="";
#ifdef _WIN32
WSADATA Data;
#endif

// Functions
int Ack(SOCKET sc);
int DoQuit(int iRet);




int DoQuit(int iRet)
//---------------------------------------------------------------------------
// Show result
//---------------------------------------------------------------------------
{
	printf("Return code = %d\n",iRet);
	return(iRet);
}


int testConnect()
//---------------------------------------------------------------------------
// Init socket, get list of mails
//---------------------------------------------------------------------------
{
	SOCKET sc;
	char s[2048],t[256];
	int i;
	

startgetmess:

	//***** Get mailfile
#ifdef _WIN32
	if (WSAStartup(MAKEWORD(1, 1), &Data) != 0) return(DoQuit(FAILED_TO_START_SOCKETS));
#endif

	//***** Create Socket
	printf("Create socket: socket(PF_INET,SOCK_STREAM)\n");
	if ((sc = socket(PF_INET,SOCK_STREAM,0)) == INVALID_SOCKET)
	{
		return(DoQuit(FAILED_TO_OBTAIN_SOCKET_HANDLE));
	}

	//***** Resolve the servers IP
	printf("Resolve IP address for: %s\n",Host);
	struct hostent *adr;
	adr = gethostbyname(Host);
	if (!adr)
	{
		return(DoQuit(FAILED_TO_RESOLVE_HOST));
	}
	
	//***** Connect to server
	SOCKADDR_IN sin;
	sin.sin_port = htons((u_short) Port);
	sin.sin_family = adr->h_addrtype;
	memcpy((char *) &sin.sin_addr, adr->h_addr, adr->h_length);
	char AddrHexa[9];
	sprintf(AddrHexa,"%08lX",* (unsigned long int *) &sin.sin_addr);
	AddrHexa[8]=0;
	printf("Connect socket to: %s\n",AddrHexa);
#ifdef _WIN32
	if (connect(sc,(LPSOCKADDR) &sin,sizeof(sin)))
#else
	if (connect(sc,(const struct sockaddr *) &sin,sizeof(sin))) 
#endif
	{
		printf("Failed to connect !\n");
		return(DoQuit(FAILED_TO_CONNECT));
	}

	//***** Server welcome message
	printf("Connected !\n");
/*
	if ((iRet=Ack(sc))) {
		return(DoQuit(iRet));
	}
*/

	//***** Disconect
	return(DoQuit(0));
}



int Ack(SOCKET sc)
//---------------------------------------------------------------------------
// Function	: Get POP response from the server.
// Input 	: sc
// Return	: O = Ok, >0 = Error
//---------------------------------------------------------------------------
{
	static char *buf;
	unsigned long int liSizeOfMail=SIZE_RECEIVE_BUFFER;
	int rlen;
	int Received = 0;

	if (!buf) 
		if ((buf = (char *) malloc(liSizeOfMail+1)) == NULL) // The first time, create buf
			return(OUT_OF_MEMORY);
again:
	if ((rlen = recv(sc,buf+Received,liSizeOfMail-Received,0)) < 1) {
		return(FAILED_TO_RECEIVE);	// Possible when pop server refuses client
	}

	buf[Received+rlen] = 0;
	Received += rlen;

	// Check for newline
	if ((buf[Received-2] != '\r') || (buf[Received-1] != '\n'))	{
		goto again; 		// Incomplete data. Line must be terminated by CRLF
	}
	return((buf[0] == '-')?1:0);
}



int main(int argc, char **argv)
//---------------------------------------------------------------------------
// MAIN
//---------------------------------------------------------------------------
{

// Read parameters
//----------------
int noarg,curseurarg,help=0,invalide=0;
char option;
char *endptr;

for (noarg=1;noarg<argc;noarg++) {
	if (((argv[noarg][0])=='/') || ((argv[noarg][0])=='-')) {
		option=(argv[noarg][1] | 0x20);
		curseurarg=2;
		if (strlen(argv[noarg]) < 3) { ++noarg; curseurarg=0; }
		switch (option) {
			case 's': strncpy(Host,argv[noarg]+curseurarg,sizeof(Host)); break;
			case 'p': Port=strtol(argv[noarg] + curseurarg, &endptr, 10); break;					// Get port from "-p80" (curseurarg = 2) or "-p 80" (curseurarg = 0)
			case '?': help=-1;break;											// Help
			case 'h': help=-1;break;											// Help
			case 'v': help=-1;break;											// Help
			default: invalide=-1;break;
		}
	}
}

// Check for conversion errors
if (*endptr != '\0') {
    // Handle error: Invalid input format
    printf("Invalid port number format\n");
    exit(-1);
}

// Check for overflow
if (Port < 0 || Port > INT_MAX) {
    // Handle error: Port number out of range
    printf("Port number out of range\n");
    exit(-1);
}

help=!(Port > 0);

// Show usage
//-----------
Usage:
if (help) {
	printf("----- %s V%s (c)Laurent Destailleur -----\n",PROG,VERSION);
	printf("%s is software that allows you to know if a TCP/IP port is used\n",PROG);
	printf("%s sources can be compiled for WIN32 (VC++, GCC CYGWIN, MINGW) or for\n");
	printf("Unix/Linux (GCC)\n",PROG);
	printf("\n");
}

if (help|invalide) {
	if (invalide) printf("----- %s V%s (c)Laurent Destailleur 2009 -----\n",PROG,VERSION);
	printf("Usage: %s params [options]\n",PROG);
	printf("Params:\n");
	printf("  -s Host                Server to test\n");
	printf("  -p Port                Port to test\n");
	printf("Options:\n");
	printf("  -v                     Print version and help information\n");
	printf("  -help                  Print version and help information\n");
	printf("\n");
	exit(-1);
}
	


// Print input values
//-------------------
printf("Port=%d\n",Port);
printf("Host=%s\n",Host);


// Check parameters
//-----------------
if (Host[0]==0) {
	invalide=-1;
	goto Usage;
}


// Action
//-------
iRet=testConnect();

return(iRet);
}
