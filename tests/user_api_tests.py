import requests

def handleApiError(response: requests.Response, expected_res_code, expected_res_body: str):
    if (response.status_code != expected_res_code or response.text != expected_res_body):
        text = "\n        ".join(response.text.split("\n"))
        if (text.__len__() == 0): text = "[No Content]"
        print(f"❌\n        Test failed with status code: {response.status_code}\n        {text}")
    else:
        print("✔")

    if (response.cookies.__len__() != 0 and response.status_code >= 400): print("        Endpoint returned cookies!!!")

test_count = 0
refresh_jar = dict()
access_jar = dict()
wrong_jar = dict()
URL = "http://127.0.0.1:8000"

test_count += 1
print(f"{test_count:>4} Create user, no display name set: ", end="")
payload = {
    "email": "tester@test.com",
    "phone_number": "123456789012345",
    "pass": "tester_pass"
}
response = requests.request("POST", URL + "/create_user", json=payload)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Create user, display name not string: ", end="")
payload = {
    "disp_name": ["tester"],
    "email": "tester@test.com",
    "phone_number": "123456789012345",
    "pass": "tester_pass"
}
response = requests.request("POST", URL + "/create_user", json=payload)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Create user, no email: ", end="")
payload = {
    "disp_name": "tester",
    "phone_number": "123456789012345",
    "pass": "tester_pass"
}
response = requests.request("POST", URL + "/create_user", json=payload)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Create user, email not string: ", end="")
payload = {
    "disp_name": "tester",
    "email": ["tester@test.com"],
    "phone_number": "123456789012345",
    "pass": "tester_pass"
}
response = requests.request("POST", URL + "/create_user", json=payload)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Create user, email with no @: ", end="")
payload = {
    "disp_name": "tester",
    "email": "testertest.com",
    "phone_number": "123456789012345",
    "pass": "tester_pass"
}
response = requests.request("POST", URL + "/create_user", json=payload)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Create user, no password set: ", end="")
payload = {
    "disp_name": "tester",
    "email": "tester@test.com",
    "phone_number": "123456789012345",
}
response = requests.request("POST", URL + "/create_user", json=payload)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Create user, password not string: ", end="")
payload = {
    "disp_name": "tester",
    "email": "tester@test.com",
    "phone_number": "123456789012345",
    "pass": ["tester_pass"]
}
response = requests.request("POST", URL + "/create_user", json=payload)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Create user, password lenght not at least 8: ", end="")
payload = {
    "disp_name": "tester",
    "email": "tester@test.com",
    "phone_number": "123456789012345",
    "pass": "tester_"
}
response = requests.request("POST", URL + "/create_user", json=payload)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Create user, no phone number: ", end="")
payload = {
    "disp_name": "tester",
    "email": "tester_no_phone@test.com",
    "pass": "tester_pass"
}
response = requests.request("POST", URL + "/create_user", json=payload)
handleApiError(response, 201, "")

test_count += 1
print(f"{test_count:>4} Create user, phone number not string: ", end="")
payload = {
    "disp_name": "tester",
    "email": "tester@test.com",
    "phone_number": ["123456789012345"],
    "pass": "tester_pass"
}
response = requests.request("POST", URL + "/create_user", json=payload)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Create user, phone number is not numeric: ", end="")
payload = {
    "disp_name": "tester",
    "email": "tester@test.com",
    "phone_number": "12345678901234a",
    "pass": "tester_pass"
}
response = requests.request("POST", URL + "/create_user", json=payload)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Create user, phone number length more than 15: ", end="")
payload = {
    "disp_name": "tester",
    "email": "tester@test.com",
    "phone_number": "1234567890123456",
    "pass": "tester_pass"
}
response = requests.request("POST", URL + "/create_user", json=payload)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Create user, method is not POST: ", end="")
payload = {
    "disp_name": "tester",
    "email": "tester@test.com",
    "phone_number": "123456789012345",
    "pass": "tester_pass"
}
response = requests.request("PATCH", URL + "/create_user", json=payload)
handleApiError(response, 405, "")

test_count += 1
print(f"{test_count:>4} Create user: ", end="")
payload = {
    "disp_name": "tester",
    "email": "tester@test.com",
    "phone_number": "123456789012345",
    "pass": "tester_pass"
}
response = requests.request("POST", URL + "/create_user", json=payload)
handleApiError(response, 201, "")

test_count += 1
print(f"{test_count:>4} Create user, user already exists: ", end="")
payload = {
    "disp_name": "tester",
    "email": "tester@test.com",
    "phone_number": "123456789012345",
    "pass": "tester_pass"
}
response = requests.request("POST", URL + "/create_user", json=payload)
handleApiError(response, 400, "User already exists")

test_count += 1
print(f"{test_count:>4} Get refresh token: ", end="")
payload = {
    "email": "tester@test.com",
    "pass": "tester_pass"
}
response = requests.request("POST", URL + "/token/get_refresh_token", json=payload)
handleApiError(response, 200, "")
refresh_jar = response.cookies

test_count += 1
print(f"{test_count:>4} Get refresh token, no email: ", end="")
payload = {
    "pass": "tester_pass"
}
response = requests.request("POST", URL + "/token/get_refresh_token", json=payload)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Get refresh token, email not string: ", end="")
payload = {
    "email": ["tester@test.com"],
    "pass": "tester_pass"
}
response = requests.request("POST", URL + "/token/get_refresh_token", json=payload)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Get refresh token, email with no @: ", end="")
payload = {
    "email": "testertest.com",
    "pass": "tester_pass"
}
response = requests.request("POST", URL + "/token/get_refresh_token", json=payload)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Get refresh token, no password: ", end="")
payload = {
    "email": "tester@test.com"
}
response = requests.request("POST", URL + "/token/get_refresh_token", json=payload)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Get refresh token, password not string: ", end="")
payload = {
    "email": "tester@test.com",
    "pass": ["tester_pass"]
}
response = requests.request("POST", URL + "/token/get_refresh_token", json=payload)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Get refresh token, password incorrect: ", end="")
payload = {
    "email": "tester@test.com",
    "pass": "tester_pass_incorrect"
}
response = requests.request("POST", URL + "/token/get_refresh_token", json=payload)
handleApiError(response, 403, "Unauthorised")

test_count += 1
print(f"{test_count:>4} Get refresh token, method is not POST: ", end="")
payload = {
    "email": "tester@test.com",
    "pass": "tester_pass"
}
response = requests.request("PATCH", URL + "/token/get_refresh_token", json=payload)
handleApiError(response, 405, "")

reuse_jar = refresh_jar.copy()
test_count += 1
print(f"{test_count:>4} Refresh refresh token: ", end="")
response = requests.request("POST", URL + "/token/refresh_refresh_token", cookies=refresh_jar)
handleApiError(response, 200, "")
refresh_jar = response.cookies
# This is so that the refresh token gets sent instead of the access token
wrong_jar = refresh_jar.copy()
for cookie in wrong_jar:
    if cookie.name == 'RefreshToken':
        cookie.name = "AccessToken"
        cookie.path = "/"
        break

test_count += 1
print(f"{test_count:>4} Refresh refresh token, reused token: ", end="")
response = requests.request("POST", URL + "/token/refresh_refresh_token", cookies=reuse_jar)
handleApiError(response, 403, "Unauthorised")

test_count += 1
print(f"{test_count:>4} Get access token: ", end="")
response = requests.request("POST", URL + "/token/get_access_token", cookies=refresh_jar)
handleApiError(response, 200, "")
access_jar = response.cookies

test_count += 1
print(f"{test_count:>4} Get access token, reused refresh token: ", end="")
response = requests.request("POST", URL + "/token/get_access_token", cookies=reuse_jar)
handleApiError(response, 403, "Unauthorised")

test_count += 1
print(f"{test_count:>4} Change display name: ", end="")
payload = {
    "new_disp_name": "testerer"
}
response = requests.request("POST", URL + "/change_disp_name", json=payload, cookies=access_jar)
handleApiError(response, 204, "")

test_count += 1
print(f"{test_count:>4} Change display name, no new display name: ", end="")
payload = {
}
response = requests.request("POST", URL + "/change_disp_name", json=payload, cookies=access_jar)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Change display name, new display name not string: ", end="")
payload = {
    "new_disp_name": ["testerer"]
}
response = requests.request("POST", URL + "/change_disp_name", json=payload, cookies=access_jar)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Change display name, new display name length longer than 200: ", end="")
payload = {
    "new_disp_name": "testerer" * 25 + "+"
}
response = requests.request("POST", URL + "/change_disp_name", json=payload, cookies=access_jar)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Change display name, wrong token: ", end="")
payload = {
    "new_disp_name": "testerer"
}
response = requests.request("POST", URL + "/change_disp_name", json=payload, cookies=wrong_jar)
handleApiError(response, 403, "Unauthorised")

test_count += 1
print(f"{test_count:>4} Change display name, no token: ", end="")
payload = {
    "new_disp_name": "testerer"
}
response = requests.request("POST", URL + "/change_disp_name", json=payload, cookies=dict())
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Change display name, method is not POST: ", end="")
payload = {
    "new_disp_name": "testerer"
}
response = requests.request("PATCH", URL + "/change_disp_name", json=payload)
handleApiError(response, 405, "")

test_count += 1
print(f"{test_count:>4} Change phone number: ", end="")
payload = {
    "new_phone_number": "12345"
}
response = requests.request("POST", URL + "/change_phone_number", json=payload, cookies=access_jar)
handleApiError(response, 204, "")

test_count += 1
print(f"{test_count:>4} Change phone number, no new phone number: ", end="")
payload = {
}
response = requests.request("POST", URL + "/change_phone_number", json=payload, cookies=access_jar)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Change phone number, new phone number not string: ", end="")
payload = {
    "new_phone_number": ["12345"]
}
response = requests.request("POST", URL + "/change_phone_number", json=payload, cookies=access_jar)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Change phone number, new phone number not numeric: ", end="")
payload = {
    "new_phone_number": "12345a"
}
response = requests.request("POST", URL + "/change_phone_number", json=payload, cookies=access_jar)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Change phone number, new phone number length longer than 15: ", end="")
payload = {
    "new_phone_number": "1234567890123456"
}
response = requests.request("POST", URL + "/change_phone_number", json=payload, cookies=access_jar)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Change phone number, wrong token: ", end="")
payload = {
    "new_phone_number": "12345"
}
response = requests.request("POST", URL + "/change_phone_number", json=payload, cookies=wrong_jar)
handleApiError(response, 403, "Unauthorised")

test_count += 1
print(f"{test_count:>4} Change phone number, no token: ", end="")
payload = {
    "new_phone_number": "12345"
}
response = requests.request("POST", URL + "/change_phone_number", json=payload, cookies=dict())
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Change phone number, method is not POST: ", end="")
payload = {
    "email": "tester@test.com",
    "new_phone_number": "12345",
    "pass": "tester_pass"
}
response = requests.request("PATCH", URL + "/change_phone_number", json=payload)
handleApiError(response, 405, "")

test_count += 1
print(f"{test_count:>4} Change password: ", end="")
payload = {
    "new_pass": "tmp_tester_pass"
}
response = requests.request("POST", URL + "/change_pass", json=payload, cookies=access_jar)
handleApiError(response, 204, "")

test_count += 1
print(f"{test_count:>4} Change password back: ", end="")
payload = {
    "new_pass": "tester_pass"
}
response = requests.request("POST", URL + "/change_pass", json=payload, cookies=access_jar)
handleApiError(response, 204, "")

test_count += 1
print(f"{test_count:>4} Change password, no new password: ", end="")
response = requests.request("POST", URL + "/change_pass", cookies=access_jar)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Change password, new password not string: ", end="")
payload = {
    "new_pass": ["tester_pass"]
}
response = requests.request("POST", URL + "/change_pass", json=payload, cookies=access_jar)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Change password, new password length not at least 8: ", end="")
payload = {
    "new_pass": "tester_",
}
response = requests.request("POST", URL + "/change_pass", json=payload, cookies=access_jar)
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Change password, wrong token: ", end="")
payload = {
    "new_pass": "tester_pass",
}
response = requests.request("POST", URL + "/change_pass", json=payload, cookies=wrong_jar)
handleApiError(response, 403, "Unauthorised")

test_count += 1
print(f"{test_count:>4} Change password, no token: ", end="")
payload = {
    "new_pass": "tester_pass",
}
response = requests.request("POST", URL + "/change_pass", json=payload, cookies=dict())
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Change password, method is not POST: ", end="")
payload = {
    "email": "tester@test.com",
    "new_pass": "tester_pass",
    "pass": "tester_pass"
}
response = requests.request("PATCH", URL + "/change_pass", json=payload)
handleApiError(response, 405, "")

test_count += 1
print(f"{test_count:>4} Delete user, wrong token: ", end="")
response = requests.request("DELETE", URL + "/delete_user", cookies=wrong_jar)
handleApiError(response, 403, "Unauthorised")

test_count += 1
print(f"{test_count:>4} Delete user, no token: ", end="")
response = requests.request("DELETE", URL + "/delete_user", cookies=dict())
handleApiError(response, 400, "Bad request")

test_count += 1
print(f"{test_count:>4} Delete user, method is not DELETE: ", end="")
response = requests.request("PATCH", URL + "/delete_user", cookies=access_jar)
handleApiError(response, 405, "")

test_count += 1
print(f"{test_count:>4} Delete user: ", end="")
response = requests.request("DELETE", URL + "/delete_user", cookies=access_jar)
handleApiError(response, 204, "")

test_count += 1
print(f"{test_count:>4} Delete user, user does not exist: ", end="")
response = requests.request("DELETE", URL + "/delete_user", cookies=access_jar)
handleApiError(response, 400, "User does not exist")


print("\n" + "-"*30+"Cleanup"+"-"*30 + "\n")


no_phone_refresh_jar = dict()
no_phone_access_jar = dict()

test_count += 1
print(f"{test_count:>4} Get refresh token for no phone user: ", end="")
payload = {
    "email": "tester_no_phone@test.com",
    "pass": "tester_pass"
}
response = requests.request("POST", URL + "/token/get_refresh_token", json=payload)
handleApiError(response, 200, "")
no_phone_refresh_jar = response.cookies

test_count += 1
print(f"{test_count:>4} Get access token for no phone user: ", end="")
response = requests.request("POST", URL + "/token/get_access_token", cookies=no_phone_refresh_jar)
handleApiError(response, 200, "")
no_phone_access_jar = response.cookies

test_count += 1
print(f"{test_count:>4} Delete no phone number user: ", end="")
response = requests.request("DELETE", URL + "/delete_user", cookies=no_phone_access_jar)
handleApiError(response, 204, "")
