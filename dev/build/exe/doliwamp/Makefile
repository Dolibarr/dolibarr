#-------------------------------------------------------------------#
# Makefile to build UsedPort exe
#-------------------------------------------------------------------#
# 1.0     Laurent Destailleur     Creation
#-------------------------------------------------------------------#
# Works with GCC CYGWIN, GCC MINGW
#-------------------------------------------------------------------#


# Project options
#-----------------

# "GCC" to use GCC GNU C++ (Cygwin or Linux), "MINGW" to use MINGW, "VC" to use Microsoft Visual C+
COMP=MINGW
# DEBUG=1 for debug
DEBUG=0

# Directories
SRCDIR  = ./
RUNDIR  = ./
INCDIR  = ./
LIBDIR  = ./
INCLUDES= -I$(INCDIR)
LIBS    = -L$(LIBDIR) -L../lib

# Program files to build        (Ex: $(RUNDIR)prog)
PROGRAM = $(RUNDIR)UsedPort
# Object files to build         (Ex: $(LIBDIR)yyy.o $(LIBDIR)zzz.o ...)
OBJLIST = $(LIBDIR)UsedPort.o$(BJ)


# Archive file for o files      (Ex: $(LIBDIR)libxxx.a)
ifeq "$(OS)" "Windows_NT"
ARCHIVE = "mylib.a"
else
ARCHIVE = "mylib.a"
endif

# Compilation/Link by GCC
#------------------------
ifeq "$(COMP)" "GCC"
CC              = gcc
CPP             = g++
RSC             = g++
AR              = ar r
OUT=-o
ifeq "$(DEBUG)" "1"
COPTIONS=-Wall -g -DDEBUG
else
COPTIONS=-O3
endif
ifeq "$(OS)" "Windows_NT"
# wsock32 required only if _WIN32 defined in source code and use WSA* functions
LOPTIONS=-Bstatic
endif
ifeq "$(MACHTYPE)" "sparc"
LOPTIONS=-lsocket -Bstatic
endif
ifeq "$(PROCESSOR_ARCHITECTURE)" "x86"
LOPTIONS2=-m486
endif
endif

# Compilation/Link by MINGW
#--------------------------
ifeq "$(COMP)" "MINGW"
CC              = mingw32-gcc.exe
CPP             = mingw32-g++.exe
RSC             = mingw32-g++.exe
AR              = ar r
OUT=-o
ifeq "$(DEBUG)" "1"
COPTIONS=-Wall -g -DDEBUG
else
COPTIONS=-O3
endif
LOPTIONS=-lwsock32 -Bstatic
ifeq "$(PROCESSOR_ARCHITECTURE)" "x86"
LOPTIONS2=-m486
endif
endif

# Compilation/Link by VC
#-----------------------
ifeq "$(COMP)" "VC"
CC              = cl.exe
CPP             = cl.exe
RSC             = rc.exe
ifeq "$(COMP)" "GCC"
COPTIONS= /nologo /ML /W3 /O2 /Yd /D "DEBUG" /D "WIN32" /D "_CONSOLE" /c
else
COPTIONS= /nologo /G4 /D "NDEBUG" /D "WIN32" /D "_CONSOLE" /c
endif
LOPTIONS= /nologo kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib\
 advapi32.lib shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib\
 odbccp32.lib wsock32.lib
BJ              = bj
OUT=/Fo
endif



# New rules
#-----------------
ifeq "$(OS)" "Windows_NT"
RM              = rm -f
CFLAGS  = $(COPTIONS) $(INCLUDES)
else
RM              = rm -f
CFLAGS  = $(COPTIONS) $(INCLUDES)
endif
LDFLAGS = $(LOPTIONS) $(LOPTIONS2) $(LIBS)

$(LIBDIR)%.o: %.cpp
	@echo ----- Compilation C++ by $(COMP) $(CPP) - $@
	@echo CPP=$(CPP)
	@echo CFLAGS=$(CFLAGS)
	$(CPP) -c $(CFLAGS) $(SRC)$< $(OUT)$@
#       @echo ----- Archivage en $(ARCHIVE)
#       $(AR) $(ARCHIVE) $@

$(LIBDIR)%.obj: %.cpp
	@echo ----- Compilation C++ by $(COMP) $(CPP) - $@
	@echo CPP=$(CPP)
	@echo CFLAGS=$(CFLAGS)
	$(CPP) -c $(CFLAGS) $(SRC)$< $(OUT)$@
#       @echo ----- Archivage en $(ARCHIVE)
#       $(AR) $(ARCHIVE) $@

$(LIBDIR)%.o: %.c
	@echo ----- Compilation C   by $(COMP) $(CC) - $@
	@echo CC=$(CC)
	@echo CFLAGS=$(CFLAGS)
	$(CC) -c $(CFLAGS) $(SRC)$< $(OUT)$@
#       @echo ----- Archivage en $(ARCHIVE)
#       $(AR) $(ARCHIVE) $@

$(LIBDIR)%.obj: %.c
	@echo ----- Compilation C   by $(COMP) $(CC) - $@
	@echo CPP=$(CPP)
	@echo CFLAGS=$(CFLAGS)
	$(CC) -c $(CFLAGS) $(SRC)$< $(OUT)$@
#       @echo ----- Archivage en $(ARCHIVE)
#       $(AR) $(ARCHIVE) $@



# Targets
#-----------------

# Default target                (Ex: $(PROGRAM) or $(OBJLIST))
all:	$(PROGRAM)

# Program (if PROGRAM is just one exe)
#$(PROGRAM1): $(OBJLIST1)
#	@echo ----- Link by $(COMP) $(CC) - $@
#	rm -f $(RUNDIR)$@
#	$(CC) -o $(RUNDIR)$@ $(OBJLIST) $(LDFLAGS) $(ARCHIVE)

# Program (if PROGRAM are several exe)
$(PROGRAM): $(OBJLIST)
	@echo ----- Link by $(COMP) $(CC) - $@
	@echo LIBDIR=$(LIBDIR)
	@echo LDFLAGS=$(LDFLAGS)
	rm -f $(RUNDIR)$@
#	$(CC) -o $(RUNDIR)$@ $(LIBDIR)$@.o$(BJ) $(LDFLAGS) $(ARCHIVE)
	$(CC) -o $(RUNDIR)$@ $(LIBDIR)$@.o$(BJ) $(LDFLAGS)

# Clean
clean:
	@echo ----- Clean
	$(RM) $(LIBDIR)*.a $(LIBDIR)*.o $(LIBDIR)*.obj $(RUNDIR)UsedPort.exe $(RUNDIR)core $(OBJLIST)


# Dependencies xxx.o: xxx.c xxxa.h xxxb.h
#$(LIBDIR)UsedPort.o: $(SRCDIR)UsedPort.cpp
