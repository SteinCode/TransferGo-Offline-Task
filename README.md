# TransferGo - Rokas Offline Task - Notification Service

This simple project was made using [Dunglas Symfony Template](https://github.com/dunglas/symfony-docker).

## Initial set up

1. Clone the repository

```bash
git clone https://github.com/SteinCode/TransferGo-Offline-Task.git
```

2. Install dependencies

```bash
composer install
```

3. In this project, credentials are stored in `.env.dev`. Add your credentials in `.env.dev`.
4. Refresh environment variables

```bash
composer refresh-env
```

5. Build fresh Docker images

```bash
docker compose build --pull --no-cache
```

6. Start the services

```bash
docker compose up --wait
```

7. Use Postman or another HTTP client to make requests against the endpoints.

## Endpoints

- `GET /send-notification` - main endpoint for sending both SMS and emails.

```json
{
    "channels": "email,sms",
    "toEmail": "johnDoe@gmail.com",
    "toSms": "+37061234567",
    "subject": "test subject",
    "body": "test message"
}
```

- `GET /send-sms` - **Deprecated**: initially used to test SMS.
- `GET /send-email` - **Deprecated**: initially used to test emails.

## Running the Worker

Asynchronous messages are processed by running the Symfony Messenger consumer. Make sure to run a worker to handle queued messages:

Start the worker inside the PHP container:

```bash
docker compose exec -t <php_container_id> bash

bin/console messenger:consume async -vv
```

## Working with env variables

To sync env variables with the running container, run:

```bash
composer refresh-env
```

## Capabilities

- Can send SMS via two different channels (providers).
- If one SMS provider is down, the other will be used.
- Can send an email via AWS SES.
- Logs usage tracking to `var/log/dev_notifications.log`.
- SMS providers can be configured by priority or enabled/disabled in `services.yaml`.
- Sends messages asynchronously.

## External tools used

- [AWS SES](https://aws.amazon.com/ses/) - for sending emails.
- [Twilio](https://www.twilio.com/en-us/messaging/channels/sms) - for sending SMS messages (primary SMS provider).
- [AWS SNS](https://aws.amazon.com/sns/) - for sending SMS messages (secondary SMS provider).

## Logging

For logging **usage tracking**, debug messages, errors, exceptions and etc. I used [Monolog](https://github.com/Seldaek/monolog).

To access logs you will need to access running docker php container:

Firstly run 
```bash
 docker ps --no-trunc 
 ```
 and copy the container id. Or if using docker desktop, navigate to php container, and copy the id from there.
Next, run 
```bash 
docker exec -it <container_id> bash
```
To check general errors or debug logs, run:
```bash
tail var/log/dev.log
```
**For usage tracking I created a seperate log file, which can be accessed by running:**
```bash 
tail var/log/dev_notifications.log
```

## Testing

Run unit tests:

```bash
composer unit-tests
```

