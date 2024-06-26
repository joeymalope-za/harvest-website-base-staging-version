<!-- @format -->

# About

Harvest WordPress website repository. This repository attempts to enable efficient team collaboration, while developing custom WordPress-based e-commerce solution. Unlike normal WordPress development, all changes are made in a local dev environment, and applied to live environments through CI/CD process. The approach is similar to [Bedrock/Trellis](https://roots.io/bedrock/), but lighter and less reliant on external components. This repo serves as an entry point to launching a local Docker-based environment, which allows to work on all Harvest services.

## Contents

### Docker-based development environment

- `Dockerfile`
- `docker-compose.yml`

### WordPress files and data

- `./public` - WordPress directory (do not make changes directly)
- `./data/dump.sql` - WordPress database dump without user tables
- `./data/dev.sql` - seed data for development environment

### Harvest plugin for WordPress (PHP)

- PHP files are in `./envs/all/`
- `./envs` dir contain subfolders corresponding to environments (all, production, staging, development). Subfolder contains environment-specific files and values files for a simple Python template engine `./scripts/set_env.py`.

### GitHub actions for automated staging and production deployments

- `.github` dir. The deployment workflow is triggered upon push to main branch, or manually. It substitutes production secrets from GitHub.

### DevOps and configuration management scripts

- `scripts/db_url_fixer.py` - Python script for replacing domains and HTTP/HTTPS when creating or deploying database dump
- `scripts/deploy_wp.sh` - shell script for production and staging deployments, intended to be run by GitHub action runner
- `scripts/export.sh` - shell script for exporting WordPress files and data into this repository, optionally, through SSH
- `scripts/start.sh` - shell script, which bootstrap local Docker-based development environment
- `scripts/update_db_dump.sh` - shell script for updating the database dump in the repository from local database container, uses `scripts/export.sh` internally

### Tests

- `./tests` dir

# Installation

## Windows

### Windows 10/11

Install WSL2 with Ubuntu 20+, then follow Linux steps.

### Other Windows versions

- Install any virtualization system (Hyper-V, VirtualBox)
- Create Ubuntu 20+ virtual machine
- Follow Linux steps
- Make sure you can access Docker containers from the host OS (network settings of VM, disable ufw on Ubuntu etc.)

## Linux and MacOS

1. Install [Docker](https://docs.docker.com/engine/install/) (on Linux, do not install Docker Desktop, unless you want it specifically). If deprecated `docker-compose` is installed, remove it and upgrade Docker to the latest version.
2. Install and configure Python >3.7, so it's available as `python3`. Follow platform-specific instructions.
3. Install `sshpass`. It's required for the scripts to access hosting providers, which allow only password authorization for SSH. On MacOS, configure `xcode` first, then install `sshpass` with the snippet:
   ```
   curl -L https://sourceforge.net/projects/sshpass/files/latest/download -o sshpass.tar.gz && tar xvzf sshpass.tar.gz
   cd sshpass-*
   ./configure
   sudo make install
   ```
4. Manually add entries to `/etc/hosts` (`windows\system32\drivers\etc\hosts`) for the services:  
   **Linux:**

   ```
   172.18.0.3 harvest.local
   172.18.0.4 id-web.local
   172.18.0.5 id-api.local
   ```

   **MacOS (can't access docker network, relies on port mapping):**

   ```
   127.0.0.1 harvest.local
   127.0.0.1 harvest-api.local
   127.0.0.1 id-web.local
   127.0.0.1 id-api.local
   127.0.0.1 mariadb
   127.0.0.1 consult.local
   127.0.0.1 queue.local
   ```

   \*\* Windows
   For windows users, the configurations are space sensitve and please make sure to assign permissions for respective groups/user.

   ![screenshot](https://github.com/theharvest420/harvest-website/assets/914416/23956c41-7d03-4010-8b29-a46674a0d388)

5. To allow working with media features (camera for id verification) from non-HTTPS sites in Chrome, add `https://harvest-dev.xyz,http://id-api.local:3000,http://id-web.local:3002` to `chrome://flags/#unsafely-treat-insecure-origin-as-secure`
6. Install `git`
7. (Optional) install PHP 8 and Composer locally to debug the code with local interpreter
8. Clone this repo and `harvest-web` repo. You need to [add a GitHub SSH key](https://docs.github.com/en/authentication/connecting-to-github-with-ssh/generating-a-new-ssh-key-and-adding-it-to-the-ssh-agent?platform=linux) first.
   ```
   git config core.fileMode false
   ```
9. Create `.env` file and fill in the variables from `docker-compose.yml`, which don't have defaults, except `WP_VERSION`:
   ```
   S3_KEY=...
   S3_SECRET=...
   ```
10. Run `scripts/start.sh`
11. Check `docker compose logs --tail 20` to see if all services have started without errors
12. The URLs:
    - https://harvest-dev.xyz - WordPress website
    - for other URLs, see `docker-compose.*.yml` of `harvest-web` repo

# Development

### How it works

#### WordPress

1. This repository contains WordPress files in `public/`, which are mounted inside WordPress container, so they are updated when WordPress is running.
2. The WordPress database engine runs in another container. It's initialized with the dump at `data/dump.sql`. All changes to the database are lost, when database container is shutdown.
3. All users, except `superadmin`, are erased, when database dump is created. If your feature requires some seed data in the database, add it to `data/dev.sql`.
4. HTTPS links and redirects in server response are rewritten by `.htaccess` for local environment

#### Harvest Web components

Harvest Web components are JavaScript modules located in a separate `harvest-web` repository. The start script launches two docker compose configuration - one of this repo, and another one of `harvest-web`.

#### Domain names

Upon export, the URLs are replaced with the values for local development. Upon deployment, the URLs are replaced with production or staging values. Aside of domains substituted with values from environments, there are hard-coded URLs in scripts: `./github/workflows/deploy*.yml; ./scripts/deploy_wp.sh; ./scripts/export.sh`. Some domain names are used both inside docker containers (as names of the services in `docker-compose.yml`), and in local hosts file, pointing to container's IP addresses. If you are adding another service, which will be deployed in development environment, make sure to use its local host name in WordPress, and then fix above mentioned scripts to replace it to correct production domain upon deploy. These scripts will replace HTTP with HTTPS as well.

### Working on features

This repository follows [Git Flow](https://docs.github.com/en/get-started/quickstart/github-flow). The tasks and deliverables are tracked as GitHub issues. Here is an outline of steps required to deliver a change:

1. Make sure you have an up-to-date `main` branch: `git pull`
2. Create a feature branch: `git checkout -b your-short-name/feature-name`
3. Update the WordPress from staging:
   ```
   SSH_PASSWORD=xxxx DB_PASSWORD=xxxx ./scripts/export.sh
   ```
   This will update the database dump and files in the `./public` dir.
4. (Recommended) Commit the 'export' commit to separate it from actual changes:
   ```
   git add public/*
   git commit -a -m 'Export from staging'
   ```
5. Run `scripts/start.sh` to launch docker environment
6. Work on the feature:
   - if it's WordPress configuration - go to `harvest.local/wp-admin` and make changes there
   - if you need to work on a server-side code - create or edit files in `envs/all/wp-content/plugins/harvest`
   - if you want to visually debug PHP functions in WordPress environment, the easiest way is to run it with a local interpreter. Make sure you have PHP dependencies installed with Composer, according to `envs/all/composer.json`, then create a test page in `tests/`. Example: `tests/geofence-test.php`.
7. If you made changes in the WordPress admin panel, or the database directly, update the database dump:
   ```
   scripts/update_db.sh
   ```
8. Commit the changes
   ```
   git add public/*
   git commit -a -m changes_description
   ```
   **Make sure you are not adding more or less than required with `git status`**
9. Push the changes with `git push`
10. Go to GitHub and create a Pull Request into `main` branch. Briefly describe the changes you have made, and mention relevant issue id. Add reviewers, or self-review. Once the PR is merge d, the changes are deployed automatically to https://harvest-staging-new.net in a few minutes.
11. Make sure the changes are working on staging.
12. Notify the QA person.

### Managing local environment

1. See WordPress logs written with PHP functions (e.g. error_log): `public/wp_content/debug.log`
1. To update WordPress files with files from `./envs/` without restarting container: `HARVEST_ENV=development python3 scripts/set_env.py`
1. Check the logs (all containers): `docker compose logs -f`
1. List running containers: `docker ps`
1. See the logs of individual services: `docker logs id_or_name_from_docker_ps`
1. Restart all services without losing data: `docker compose restart`
1. Stop all services without losing data: `docker compose stop`
1. Shutdown all services: `docker compose down`. **WARNING: all database changes since the container start will be lost**
1. Clear all local changes: `git checkout --force .`
1. Access https://harvest-dev.xyz/wp-admin as `superadmin/superadmin`
1. Access the database using `docker exec -it harvest-website-mariadb-1 mysql -uharvestj -pharvestj`
1. Connect the terminal to website container: `docker exec -it harvest-website-wordpress-1 bash`

### Going through the user registration flow in a local environment

1. Fill the registration form. If you want to bypass the doctor call, use phone number: 0408342974
2. On the id verification step, deny the camera permissions and upload images manually
3. On the bot step, answer with 'iddqd' to the first question to skip other questions and get CBD treatment approval
4. The bot invokes a callback to transfer results, which will not work in a local environment, so it will look like the bot step starts over and over again. Look up the `chat_session` in a WP user meta, replace it in the following JSON and trigger the callback manually to progress:
   ```
   curl -X 'POST' 'https://harvest-dev.xyz/wp-json/harvest-api/virtual-doctor-results' -H 'connection: close' -H 'accept-encoding: gzip, deflate, br' -H 'user-agent: Google-Dialogflow' -H 'accept: */*' -H 'content-type: application/json' -H 'authorization: Basic R3Q4VXN5RzJVOnh3WXFZRVl6SThD' -H 'php-auth-user: Gt8UsyG2U' -H 'php-auth-pw: xwYqYEYzI8C' -d '{
     "sessionInfo": {
       "session": "sessions/CHAT_SESSION"
     },
     "fulfillmentInfo": {
       "tag": "THC"
     },
     "messages": [
       {
         "payload": {
           "richContent": [
             [
               {
                 "text": "Chat with the doctor",
                 "icon": {
                   "color": "#F78A2D",
                   "type": "store"
                 },
                 "type": "button"
               }
             ]
           ],
           "result": {
             "doctor_approval_required": true,
             "patient_card": "Gender: female Occupation: driver Condition: Anxiety Therapies attempted: Sertraline,Diazepam Allergies: hemp Drugs in use: none Suggested dosage: Oral THC 5 - 10 mg Once per day Oral CBD 25 - 600 mg Once per day ",
             "condition": "Anxiety"
           }
         }
       }
     ]
   }'
   ```
5. Refresh the page, and it will go to the SalesIQ chat on the shop page, or will unlock the shop right away, if magic phone number was used. Click 'Wait in the queue'.
6. Go to SalesIQ dashboard, accept the chat, open CRM contact, add prescription, send /start, enter daily.co meeting as a doctor, and enter the meeting as a patient. Wait for 60 seconds, then finish the meeting.
7. Imitate Zoho - WordPress callback with doctor call results, substituting 'harvest_uuid' with 'uuid' meta field of the patient:
   ```
   curl -XPOST 'https://harvest-dev.xyz/wp-json/harvest-api/update_membership?auth_token=R3Q4VXN5RzJVOnh3WXFZRVl6SThD' -H 'Content-Type: application/json' -d '{"harvest_uuid":"HARVEST_UUID","zoho_id":"51445000001507041","prescription_approved":"true","rejection_reason":"none"}'
   ```
8. The page will display approval message and the shop will unlock

# Appendix A: Where We Have Hardcoded Urls

1. DialogFlow Webhooks - API forwarder URLs deployed on Kinsta
2. harvest-api repo, in API call forwarder - where to forward API calls (WordPress REST API)
3. Zoho Plugs used in Zobot - API forwarder URLs deployed on Kinsta
4. Zoho API endpoints in harvest.php of this repo
