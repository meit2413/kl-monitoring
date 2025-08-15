# WHM Server Monitoring

This is a master backend system that allows you to add multiple WHM servers and monitor them using the WHM API. The system gathers and displays metrics such as server load average, disk usage, SSL certificate expiration dates, WHM version, and backup status for each added server.

## Features

- Add and manage multiple WHM servers.
- Dashboard to view all server metrics in one place.
- Highlights outdated WHM versions.
- Highlights SSL certificates that are expiring soon.
- Fetches data in the background to keep the dashboard fast.

## Installation

1.  **Clone the repository:**
    ```bash
    git clone <repository_url>
    ```

2.  **Database Setup:**
    - Create a new MySQL database.
    - Import the `database.sql` file to create the necessary tables.
    ```bash
    mysql -u your_user -p your_database < database.sql
    ```

3.  **Configuration:**
    - Rename `config.php.example` to `config.php`.
    - Edit `config.php` and enter your database credentials.

4.  **Web Server:**
    - Point your web server's document root to the `public` directory.
    - Make sure the web server has permissions to write to the project directory (or at least to a log directory if you implement logging).

5.  **Cron Job:**
    - Set up a cron job to run the `app/fetch_data.php` script periodically. This script fetches the data from your WHM servers and stores it in the local database.
    - Example cron job that runs every 30 minutes:
    ```bash
    */30 * * * * /usr/bin/php /path/to/your/project/app/fetch_data.php
    ```

## Usage

1.  Open the application in your web browser.
2.  Click on "Add New Server" to add your WHM servers. You will need the server's hostname, WHM username, and an API token.
3.  Once you have added servers, go to the "View Dashboard" to see their metrics.

## Notes and Considerations

-   **CPU Usage:** The application monitors the server load average, which is a standard indicator of server load and includes CPU usage. The WHM API does not provide a direct, isolated CPU percentage metric, so load average is used instead as a comprehensive measure of system activity.

-   **WHM Stable Version:** The dashboard compares the installed WHM version against a hardcoded stable version number to check if it's outdated. For a production environment, you should modify the code to dynamically fetch the latest stable version number from cPanel's official release tier page or another reliable source.

-   **Security:**
    -   The application enables SSL certificate verification for all WHM API calls. This is a crucial security measure. If you are having trouble connecting to your WHM server, it might be because its SSL certificate is not trusted by the server running this monitoring application. Ensure that your WHM server has a valid SSL certificate and that its Certificate Authority (CA) is in the trusted store of your monitoring server.
    -   The API tokens are stored in the database in plaintext. For enhanced security, you should implement a mechanism to encrypt these tokens before storing them.
    -   This application does not include user authentication. It is intended to be run in a trusted environment. For production use, you should implement a login system to protect the dashboard.

-   **Error Handling:** The current error handling is basic. For a production system, you should implement a more robust logging mechanism (e.g., using a library like Monolog) to log API errors and other issues for easier debugging.
I will create a `config.php.example` file to be more aligned with the `README.md` file.

And I will delete the `config.php` file.
I have enabled SSL verification. Now I will create a `README.md` to provide instructions and clarify code review points, then create a `config.php.example` file and delete the old `config.php`.
