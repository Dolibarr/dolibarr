#!/usr/bin/env python3
# pylint: disable=invalid-name
"""
Convert a log to CheckStyle format.

Url: https://github.com/mdeweerd/LogToCheckStyle

The log can then be used for generating annotations in a github action.

Note: this script is very young and "quick and dirty".
      Patterns can be added to "PATTERNS" to match more messages.

# Examples

Assumes that logToCs.py is available as .github/logToCs.py.

## Example 1:


```yaml
      - run: |
          pre-commit run -all-files | tee pre-commit.log
          .github/logToCs.py pre-commit.log pre-commit.xml
      - uses: staabm/annotate-pull-request-from-checkstyle-action@v1
        with:
          files: pre-commit.xml
          notices-as-warnings: true # optional
```

## Example 2:


```yaml
      - run: |
          pre-commit run --all-files | tee pre-commit.log
      - name: Add results to PR
        if: ${{ always() }}
        run: |
          .github/logToCs.py pre-commit.log | cs2pr
```

Author(s):
  - https://github.com/mdeweerd

License: MIT License

"""

import argparse
import datetime as dt
import json
import os
import re
import sys
import xml.etree.ElementTree as ET  # nosec


def remove_prefix(string, prefix):
    """
    Remove prefix from string

    Provided for backward compatibility.
    """
    if prefix and string.startswith(prefix):
        return string[len(prefix) :]
    return string


def convert_notices_to_checkstyle(notices, root_path=None):
    """
    Convert annotation list to CheckStyle xml string
    """
    root = ET.Element("checkstyle")
    for fields in notices:
        add_error_entry(root, **fields, root_path=root_path)
    return ET.tostring(root, encoding="utf_8").decode("utf_8")


def convert_lines_to_notices(lines):
    """
    Convert provided message to CheckStyle format.
    """
    notices = []
    for line in lines:
        fields = parse_message(line)
        if fields:
            notices.append(fields)
    return notices


def convert_text_to_notices(text):
    """
    Convert provided message to CheckStyle format.
    """
    return parse_file(text)


# Initial version for Checkrun from:
# https://github.com/tayfun/flake8-your-pr/blob/50a175cde4dd26a656734c5b64ba1e5bb27151cb/src/main.py#L7C1-L123C36
# MIT Licence
class CheckRun:
    """
    Represents the check run
    """

    GITHUB_TOKEN = os.environ.get("GITHUB_TOKEN", None)
    GITHUB_EVENT_PATH = os.environ.get("GITHUB_EVENT_PATH", None)

    URI = "https://api.github.com"
    API_VERSION = "2022-11-28"
    ACCEPT_HEADER_VALUE = "application/vnd.github+json"
    AUTH_HEADER_VALUE = f"Bearer {GITHUB_TOKEN}"
    # This is the max annotations Github API accepts in one go.
    MAX_ANNOTATIONS = 50

    def __init__(self):
        """
        Initialise Check Run object with information from checkrun
        """
        self.read_event_file()
        self.read_meta_data()

    def read_event_file(self):
        """
        Read the event file to get the event information later.
        """
        if self.GITHUB_EVENT_PATH is None:
            raise ValueError("Not running in github workflow")
        with open(self.GITHUB_EVENT_PATH, encoding="utf_8") as event_file:
            self.event = json.loads(event_file.read())

    def read_meta_data(self):
        """
        Get meta data from event information
        """
        self.repo_full_name = self.event["repository"]["full_name"]
        pull_request = self.event.get("pull_request")
        print("%r", self.event)
        if pull_request:
            self.head_sha = pull_request["head"]["sha"]
        else:
            print("%r", self.event)
            check_suite = self.event.get("check_suite", None)
            if check_suite is not None:
                self.head_sha = check_suite["pull_requests"][0]["base"]["sha"]
            else:
                self.head_sha = None  # Can't annotate?

    def submit(  # pylint: disable=too-many-arguments
        self,
        notices,
        title=None,
        summary=None,
        text=None,
        conclusion=None,
    ):
        """
        Submit annotations to github

        See:
        https://docs.github.com/en/rest/checks/runs?apiVersion=2022-11-28
              #update-a-check-run

        :param conclusion: success, failure
        """
        # pylint: disable=import-outside-toplevel
        import requests  # Import here to not impose presence of module

        if self.head_sha is None:
            return

        output = {
            "annotations": notices[: CheckRun.MAX_ANNOTATIONS],
        }
        if title is not None:
            output["title"] = title
        if summary is not None:
            output["summary"] = summary
        if text is not None:
            output["text"] = text
        if conclusion is None:
            # action_required, cancelled, failure, neutral, success
            # skipped, stale, timed_out
            if bool(notices):
                conclusion = "failure"
            else:
                conclusion = "success"

        payload = {
            "name": "log-to-pr-annotation",
            "head_sha": self.head_sha,
            "status": "completed",  # queued, in_progress, completed
            "conclusion": conclusion,
            # "started_at": dt.datetime.now(dt.timezone.utc).isoformat(),
            "completed_at": dt.datetime.now(dt.timezone.utc).isoformat(),
            "output": output,
        }

        # Create the check-run
        response = requests.post(
            f"{self.URI}/repos/{self.repo_full_name}/check-runs",
            headers={
                "Accept": self.ACCEPT_HEADER_VALUE,
                "Authorization": self.AUTH_HEADER_VALUE,
                "X-GitHub-Api-Version": self.API_VERSION,
            },
            json=payload,
            timeout=30,
        )
        print(response.content)
        response.raise_for_status()


ANY_REGEX = r".*?"
FILE_REGEX = r"\s*(?P<file_name>\S.*?)\s*?"
FILEGROUP_REGEX = r"\s*(?P<file_group>\S.*?)\s*?"
EOL_REGEX = r"[\r\n]"
LINE_REGEX = r"\s*(?P<line>\d+?)\s*?"
COLUMN_REGEX = r"\s*(?P<column>\d+?)\s*?"
SEVERITY_REGEX = r"\s*(?P<severity>error|warning|notice|style|info)\s*?"
MSG_REGEX = r"\s*(?P<message>.+?)\s*?"
MULTILINE_MSG_REGEX = r"\s*(?P<message>(?:.|.[\r\n])+)"
# cpplint confidence index
CONFIDENCE_REGEX = r"\s*\[(?P<confidence>\d+)\]\s*?"


# List of message patterns, add more specific patterns earlier in the list
# Creating patterns by using constants makes them easier to define and read.
PATTERNS = [
    # beautysh
    #  File ftp.sh: error: "esac" before "case" in line 90.
    re.compile(
        f"^File {FILE_REGEX}:{SEVERITY_REGEX}:"
        f" {MSG_REGEX} in line {LINE_REGEX}.$"
    ),
    # beautysh
    #  File socks4echo.sh: error: indent/outdent mismatch: -2.
    re.compile(f"^File {FILE_REGEX}:{SEVERITY_REGEX}: {MSG_REGEX}$"),
    # yamllint
    # ##[group].pre-commit-config.yaml
    # ##[error]97:14 [trailing-spaces] trailing spaces
    # ##[endgroup]
    re.compile(rf"^##\[group\]{FILEGROUP_REGEX}$"),  # Start file group
    re.compile(
        rf"^##\[{SEVERITY_REGEX}\]{LINE_REGEX}:{COLUMN_REGEX}{MSG_REGEX}$"
    ),  # Msg
    re.compile(r"^##(?P<file_endgroup>\[endgroup\])$"),  # End file group
    #  File socks4echo.sh: error: indent/outdent mismatch: -2.
    re.compile(f"^File {FILE_REGEX}:{SEVERITY_REGEX}: {MSG_REGEX}$"),
    # Emacs style
    #  path/to/file:845:5: error - Expected 1 space after closing brace
    re.compile(
        rf"^{FILE_REGEX}:{LINE_REGEX}:{COLUMN_REGEX}:{SEVERITY_REGEX}"
        rf"-?\s{MSG_REGEX}$"
    ),
    # ESLint (JavaScript Linter), RoboCop, shellcheck
    #  path/to/file.js:10:2: Some linting issue
    #  path/to/file.rb:10:5: Style/Indentation: Incorrect indentation detected
    #  path/to/script.sh:10:1: SC2034: Some shell script issue
    re.compile(f"^{FILE_REGEX}:{LINE_REGEX}:{COLUMN_REGEX}: {MSG_REGEX}$"),
    # Cpplint default output:
    #           '%s:%s:  %s  [%s] [%d]\n'
    #   % (filename, linenum, message, category, confidence)
    re.compile(f"^{FILE_REGEX}:{LINE_REGEX}:{MSG_REGEX}{CONFIDENCE_REGEX}$"),
    # MSVC
    # file.cpp(10): error C1234: Some error message
    re.compile(
        f"^{FILE_REGEX}\\({LINE_REGEX}\\):{SEVERITY_REGEX}{MSG_REGEX}$"
    ),
    # Java compiler
    # File.java:10: error: Some error message
    re.compile(f"^{FILE_REGEX}:{LINE_REGEX}:{SEVERITY_REGEX}:{MSG_REGEX}$"),
    # Python
    # File ".../logToCs.py", line 90 (note: code line follows)
    re.compile(f'^File "{FILE_REGEX}", line {LINE_REGEX}$'),
    # Pylint, others
    # path/to/file.py:10: [C0111] Missing docstring
    # others
    re.compile(f"^{FILE_REGEX}:{LINE_REGEX}: {MSG_REGEX}$"),
    # Shellcheck:
    # In script.sh line 76:
    re.compile(
        f"^In {FILE_REGEX} line {LINE_REGEX}:{EOL_REGEX}?"
        f"({MULTILINE_MSG_REGEX})?{EOL_REGEX}{EOL_REGEX}"
    ),
    # eslint:
    #  /path/to/filename
    #    14:5  error  Unexpected trailing comma  comma-dangle
    re.compile(
        f"^{FILE_REGEX}{EOL_REGEX}"
        rf"\s+{LINE_REGEX}:{COLUMN_REGEX}\s+{SEVERITY_REGEX}\s+{MSG_REGEX}$"
    ),
]

# Exceptionnaly some regexes match messages that are not error.
# This pattern matches those exceptions
EXCLUDE_MSG_PATTERN = re.compile(
    r"^("
    r"Placeholder pattern"  # To remove on first message pattern
    r")"
)

# Exceptionnaly some regexes match messages that are not error.
# This pattern matches those exceptions
EXCLUDE_FILE_PATTERN = re.compile(
    r"^("
    # Codespell:  (appears as a file name):
    r"Used config files\b"
    r")"
)

# Severities available in CodeSniffer report format
SEVERITY_NOTICE = "notice"
SEVERITY_WARNING = "warning"
SEVERITY_ERROR = "error"


def strip_ansi(text: str):
    """
    Strip ANSI escape sequences from string (colors, etc)
    """
    return re.sub(r"\x1B(?:[@-Z\\-_]|\[[0-?]*[ -/]*[@-~])", "", text)


def parse_file(text):
    """
    Parse all messages in a file

    Returns the fields in a dict.
    """
    # pylint: disable=too-many-branches,too-many-statements
    # regex required to allow same group names
    try:
        import regex  # pylint: disable=import-outside-toplevel
    except ImportError as exc:
        raise ImportError(
            "The 'parsefile' method requires 'python -m pip install regex'"
        ) from exc

    patterns = [pattern.pattern for pattern in PATTERNS]
    # patterns = [PATTERNS[0].pattern]

    file_group = None  # The file name for the group (if any)
    full_regex = "(?:(?:" + (")|(?:".join(patterns)) + "))"
    results = []

    for fields in regex.finditer(
        full_regex, strip_ansi(text), regex.MULTILINE | regex.IGNORECASE
    ):
        if not fields:
            continue
        result = fields.groupdict()

        if len(result) == 0:
            continue

        severity = result.get("severity", None)
        file_name = result.get("file_name", None)
        confidence = result.pop("confidence", None)
        new_file_group = result.pop("file_group", None)
        file_endgroup = result.pop("file_endgroup", None)
        message = result.get("message", None)

        if new_file_group is not None:
            # Start of file_group, just store file
            file_group = new_file_group
            continue

        if file_endgroup is not None:
            file_group = None
            continue

        if file_name is None:
            if file_group is not None:
                file_name = file_group
                result["file_name"] = file_name
            else:
                # No filename, skip
                continue
        else:
            if EXCLUDE_FILE_PATTERN.search(file_name):
                # This file_name is excluded
                continue

        if message is not None:
            if EXCLUDE_MSG_PATTERN.search(message):
                # This message is excluded
                continue

        if confidence is not None:
            # Convert confidence level of cpplint
            # to warning, etc.
            confidence = int(confidence)

            if confidence <= 1:
                severity = SEVERITY_NOTICE
            elif confidence >= 5:
                severity = SEVERITY_ERROR
            else:
                severity = SEVERITY_WARNING

        if severity is None:
            severity = SEVERITY_ERROR
        else:
            severity = severity.lower()

        if severity in ["info", "style"]:
            severity = SEVERITY_NOTICE

        result["severity"] = severity

        results.append(result)

    return results


def parse_message(message):
    """
    Parse message until it matches a pattern.

    Returns the fields in a dict.
    """
    for pattern in PATTERNS:
        fields = pattern.match(message, re.IGNORECASE)
        if not fields:
            continue
        result = fields.groupdict()
        if len(result) == 0:
            continue

        if "confidence" in result:
            # Convert confidence level of cpplint
            # to warning, etc.
            confidence = int(result["confidence"])
            del result["confidence"]

            if confidence <= 1:
                severity = SEVERITY_NOTICE
            elif confidence >= 5:
                severity = SEVERITY_ERROR
            else:
                severity = SEVERITY_WARNING
            result["severity"] = severity

        if "severity" not in result:
            result["severity"] = SEVERITY_ERROR
        else:
            result["severity"] = result["severity"].lower()

        if result["severity"] in ["info", "style"]:
            result["severity"] = SEVERITY_NOTICE

        return result

    # Nothing matched
    return None


def add_error_entry(  # pylint: disable=too-many-arguments
    root,
    severity,
    file_name,
    line=None,
    column=None,
    message=None,
    source=None,
    root_path=None,
):
    """
    Add error information to the CheckStyle output being created.
    """
    file_element = find_or_create_file_element(
        root, file_name, root_path=root_path
    )
    error_element = ET.SubElement(file_element, "error")
    error_element.set("severity", severity)
    if line:
        error_element.set("line", line)
    if column:
        error_element.set("column", column)
    if message:
        error_element.set("message", message)
    if source:
        # To verify if this is a valid attribute
        error_element.set("source", source)


def find_or_create_file_element(root, file_name: str, root_path=None):
    """
    Find/create file element in XML document tree.
    """

    if root_path is not None:
        file_name = remove_prefix(file_name, root_path)
    for file_element in root.findall("file"):
        if file_element.get("name") == file_name:
            return file_element
    file_element = ET.SubElement(root, "file")
    file_element.set("name", file_name)
    return file_element


def main():
    """
    Parse the script arguments and get the conversion done.
    """
    parser = argparse.ArgumentParser(
        description="Convert messages to Checkstyle XML format."
    )
    parser.add_argument(
        "input", help="Input file. Use '-' for stdin.", nargs="?", default="-"
    )
    parser.add_argument(
        "output",
        help="Output file. Use '-' for stdout.",
        nargs="?",
        default="-",
    )
    parser.add_argument(
        "-i",
        "--in",
        dest="input_named",
        help="Input filename. Overrides positional input.",
    )
    parser.add_argument(
        "-o",
        "--out",
        dest="output_named",
        help="Output filename. Overrides positional output.",
    )
    parser.add_argument(
        "--root",
        metavar="ROOT_PATH",
        help="Root directory to remove from file paths."
        "  Defaults to working directory.",
        default=os.getcwd(),
    )
    parser.add_argument(
        "--github-annotate",
        action=argparse.BooleanOptionalAction,
        help="Annotate when in Github workflow.",
        # Currently disabled,
        #  Future: (os.environ.get("GITHUB_EVENT_PATH", None) is not None),
        default=False,
    )

    args = parser.parse_args()

    if args.input == "-" and args.input_named:
        with open(
            args.input_named, encoding="utf_8", errors="surrogateescape"
        ) as input_file:
            text = input_file.read()
    elif args.input != "-":
        with open(
            args.input, encoding="utf_8", errors="surrogateescape"
        ) as input_file:
            text = input_file.read()
    else:
        text = sys.stdin.read()

    root_path = os.path.join(args.root, "")

    try:
        notices = convert_text_to_notices(text)
    except ImportError:
        notices = convert_lines_to_notices(re.split(r"[\r\n]+", text))

    checkstyle_xml = convert_notices_to_checkstyle(
        notices, root_path=root_path
    )

    if args.output == "-" and args.output_named:
        with open(args.output_named, "w", encoding="utf_8") as output_file:
            output_file.write(checkstyle_xml)
    elif args.output != "-":
        with open(args.output, "w", encoding="utf_8") as output_file:
            output_file.write(checkstyle_xml)
    else:
        print(checkstyle_xml)

    if args.github_annotate:
        checkrun = CheckRun()
        checkrun.submit(notices)


if __name__ == "__main__":
    main()
