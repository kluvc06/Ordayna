# Setup
## Linux
### Bare metal
***WARNING: This will replace the entire contents of /etc/nginx. If you deem this unacceptable use the docker setup.***  
- Run config/setup.sh  
- This will install all dependancies (including nginx, mariadb, php-fpm and composer) and configure nginx  
- By default the web server is exposed on localhost:80 and localhost:443  
### Docker
***WARNING: This web server is not secure and should not be exposed to the open web***  
- Run config/docker_setup.sh  
- This will install docker and setup a mariadb docker image and a custom debian based docker image running the php built-in web server  
- By default the web server is exposed on localhost:80  
## Windows
### Docker
***WARNING: This web server is not secure and should not be exposed to the open web***  
- Install docker desktop and ensure that docker engine is running  
- Run config/docker_setup.bat  
- This will setup a mariadb docker image and a custom debian based docker image running the php built-in web server  
- By default the web server is exposed on localhost:80
# Running api tests
***NOTE: If the website was setup via docker then the url of the website has to be changed in user_api_tests.py and one of the tests that tests sending a payload which is too large will fail due to how this limit is enforced between bare metal and docker setups.***  
- Can be run with:  
  - On linux: ```python3 tests/user_api_tests.py```  
  - On Windows: ```python tests/user_api_tests.py```  
