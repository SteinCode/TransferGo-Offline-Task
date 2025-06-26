# TransferGo - Rokas Offline Task - Notification Service

This simple project was made using [Dunglas Symfony Template](https://github.com/dunglas/symfony-docker).

## Initial set up

1. Run `docker compose build --pull --no-cache` to build fresh images.
2. Run `docker compose up --wait` to set up.
3. We recommend using Postman or other similar app or web to run requests.

## Endpoints

### `GET test-notifications` - main endpoint for sending both: SMS and emails.

## Working with env variables

To sync env variables with the running container run custom composer command:

`composer refresh-env`

## Capabilities

-   Can send sms via two different channels (providers), if one of the channels is down, another will be used.
-   Can send an email via one channel.
-   Logs usage tracking to dev_notifications.log.

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
