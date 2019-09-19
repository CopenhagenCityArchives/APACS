FROM alpine:latest

RUN apk add openssh rsync sshpass

ENTRYPOINT rsync -r -v -e "sshpass -p $KBHKILDER_SSH_PASSWORD ssh -o StrictHostKeyChecking=no" --exclude /tests* --exclude vendor/**/test** --exclude *.yml --exclude .env --exclude composer.* --exclude phpunit.xml --exclude .dockerignore /code/ $KBHKILDER_SSH_USER@$KBHKILDER_SSH_HOST:$KBHKILDER_SSH_PATH
