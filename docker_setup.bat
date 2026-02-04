@echo off
echo Make sure that docker desktop is installed

cd ../backend
docker build . -t ordayna-backend
echo database:3306 > database_address
cd ../config

echo Run by double clicking on run_on_windows.bat in the project root folder

