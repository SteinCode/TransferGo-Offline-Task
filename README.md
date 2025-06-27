# TransferGo - Rokas Offline Task - Notification Service

This simple project was made using [Dunglas Symfony Template](https://github.com/dunglas/symfony-docker).

## Initial set up

1. Clone the repository

```bash
git clone https://github.com/SteinCode/TransferGo-Offline-Task.git
```

2. In this project for credential storage I am using `.env.dev`. Add your credentials in `.env.dev`.
3. Run `composer refresh-env`.
4. Run `docker compose build --pull --no-cache` to build fresh images.
5. Run `docker compose up --wait` to set up.
6. We recommend using Postman or other similar app to run requests.

## Endpoints

-   `GET send-notification` - main endpoint for sending both: SMS and emails.

```JSON
{
    "channels": "email,sms",
    "toEmail": "johnDoe@gmail.com",
    "toSms": "+37061234567",
    "subject": "test subject",
    "message": "test message"
}
```

-   `GET send-sms` - marked as deprecated, initially was used to test sms.
-   `GET send-email` - marked as deprecated, initially was used to test emails.

## Working with env variables

To sync env variables with the running container run custom composer command:

```bash
composer refresh-env
```

## Capabilities

-   Can send sms via two different channels (providers). \
-   if one of the SMS channels is down, another will be used.
-   Can send an email via one channel.
-   Logs usage tracking to dev_notifications.log.
-   SMS providers can be configured by priority or enabled/disabled in `services.yaml`.

## External tools used

-   [AWS SES](https://aws.amazon.com/ses/) - for sending emails.
-   [Twilio](https://www.twilio.com/en-us/messaging/channels/sms) - for sending SMS messages (as primary SMS provider).
-   [AWS SNS](https://aws.amazon.com/sns/) - for sending SMS messages (as secondary SMS provider).

## Logging

For logging **usage tracking**, debug messages, errors, exceptions and etc. I used [Monolog](https://github.com/Seldaek/monolog).

To access logs you will need to access running docker php container:

-   First run `docker ps --no-trunc` and copy the container id or if using docker desktop, navigate to php-1 container, and copy the id from there.
-   Run `docker exec -it <container_id> bash`.
-   To check general errors or debug logs, run `tail var/log/dev.log`.
-   **For usage tracking I created a seperate log file, which can be accessed by running `tail var/log/dev_notifications.log`.**

## Testing

To run tests, you can use the following command:

```bash
composer unit-tests
```
