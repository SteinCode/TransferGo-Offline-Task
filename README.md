# TransferGo - Rokas Offline Task - Notification Service

This simple project was made using [Dunglas Symfony Template](https://github.com/dunglas/symfony-docker).

## Initial set up

0. When you will be testing this project, we assume that you have [Docker](https://www.docker.com/), [Docker Compose](https://docs.docker.com/compose/), PHP and Composer installed on your machine.
1. First, run `docker-compose up --wait`.

## Working with env variables

In this project I used `.env.dev` file for managing all the environment variables.

## Capabilities

-   Can send sms via two different channels (providers), if one of the channels is down, another will be used.
-   Can send an email via one channel.
-   Logs
-   ...

## Endpoints

### `GET test-notifications` - main endpoint for sending both: SMS and emails.

## Tools used

-   [AWS SES](https://aws.amazon.com/ses/) - for sending emails.
-   [Twilio](https://www.twilio.com/en-us/messaging/channels/sms) - for sending SMS messages (as primary SMS provider).
-   [AWS SNS](https://aws.amazon.com/sns/) - for sending SMS messages (as secondary SMS provider).

## Logging

For logging **usage tracking**, debug messages, errors, exceptions and etc. I used [Monolog](https://github.com/Seldaek/monolog).

To access logs you will need to access running docker php container:

-   First run `docker ps --no-trunc` and copy the container id or if useing docker desktop, navigate to php-1 container, and copy the id from there.
-   Run `docker exec -it <container_id> bash`.
-   To check general errors or debug logs, run `tail var/log/dev.log`.
-   **For usage tracking I created a seperate log file, which can be accessed by running `tail var/log/dev_notifications.log`**
