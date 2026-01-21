import requests

def handleApiError(response: requests.Response, expected_res_code, expected_res_body: str):
    if (response.status_code != expected_res_code or response.text != expected_res_body):
        text = "\n        ".join(response.text.split("\n"))
        if (text.__len__() == 0): text = "[No Content]"
        print(f"❌\n        Test failed with status code: {response.status_code}\n        {text}")
    else:
        print("✔")

    if (response.cookies.__len__() != 0 and response.status_code >= 400): print("        Endpoint returned cookies!!!")

def testEndpoint(message: str, method: str, endpoint_path: str, cookies, payload: dict(), expected_res_code, expected_res_body):
    global test_count
    test_count += 1
    print(f"{test_count:>4} {message}: ", end="")

    response = requests.request(method, URL + endpoint_path, json=payload, cookies=cookies)
    handleApiError(response, expected_res_code, expected_res_body)
    return response

def testEndpointNoErrorHandling(message: str, method: str, endpoint_path: str, cookies, payload: dict()):
    global test_count
    test_count += 1
    print(f"{test_count:>4} {message}: ", end="")

    return requests.request(method, URL + endpoint_path, json=payload, cookies=cookies)

test_count = 0
refresh_jar = dict()
access_jar = dict()
wrong_access_jar = dict()
wrong_refresh_jar = dict()
reuse_refresh_jar = dict()
intezmeny_id = 0
URL = "http://127.0.0.1:8000"

def main():
    createUser()
    tokens()
    changeUserData()
    createIntezmeny()
    getEndpoints()
    deleteIntezmeny()
    deleteUser()

    print("\n" + "-"*30+"Cleanup"+"-"*30 + "\n")
    cleanup()


def createUser():
    testEndpoint("Create user, no display name", "POST", "/create_user", "",
                 {"email": "tester@test.com", "phone_number": "123456789012345", "pass": "tester_pass"},
                 400, "Bad request")
    testEndpoint("Create user, display name empty", "POST", "/create_user", "",
                 {"disp_name": "", "email": "tester@test.com", "phone_number": "123456789012345", "pass": "tester_pass"},
                 400, "Bad request")
    testEndpoint("Create user, display name length longer than 200", "POST", "/create_user", "",
                 {"disp_name": "tester" * 40, "email": "tester@test.com", "phone_number": "123456789012345", "pass": "tester_pass"},
                 400, "Bad request")
    testEndpoint("Create user, display name not string", "POST", "/create_user", "",
                 {"disp_name": ["tester"], "email": "tester@test.com", "phone_number": "123456789012345", "pass": "tester_pass"},
                 400, "Bad request")
    testEndpoint("Create user, no email", "POST", "/create_user", "",
                 {"disp_name": "tester", "phone_number": "123456789012345", "pass": "tester_pass"},
                 400, "Bad request")
    testEndpoint("Create user, email empty", "POST", "/create_user", "",
                 {"disp_name": "tester", "email": "", "phone_number": "123456789012345", "pass": "tester_pass"},
                 400, "Bad request")
    testEndpoint("Create user, email not string", "POST", "/create_user", "",
                 {"disp_name": "tester", "email": ["tester@test.com"], "phone_number": "123456789012345", "pass": "tester_pass"},
                 400, "Bad request")
    testEndpoint("Create user, email length longer than 254", "POST", "/create_user", "",
                 {"disp_name": "tester", "email": "tester@test.com" * 30, "phone_number": "123456789012345", "pass": "tester_pass"},
                 400, "Bad request")
    testEndpoint("Create user, with no @", "POST", "/create_user", "",
                 {"disp_name": "tester", "email": "testertest.com", "phone_number": "123456789012345", "pass": "tester_pass"},
                 400, "Bad request")
    testEndpoint("Create user, no password", "POST", "/create_user", "",
                 {"disp_name": "tester", "email": "tester@test.com", "phone_number": "123456789012345"},
                 400, "Bad request")
    testEndpoint("Create user, password not string", "POST", "/create_user", "",
                 {"disp_name": "tester", "email": "tester@test.com", "phone_number": "123456789012345", "pass": ["tester_pass"]},
                 400, "Bad request")
    testEndpoint("Create user, password length not at least 8", "POST", "/create_user", "",
                 {"disp_name": "tester", "email": "tester@test.com", "phone_number": "123456789012345", "pass": "tester_"},
                 400, "Bad request")
    testEndpoint("Create user, no phone number", "POST", "/create_user", "",
                 {"disp_name": "tester", "email": "tester_no_phone@test.com", "pass": "tester_pass"},
                 201, "")
    testEndpoint("Create user, phone number empty", "POST", "/create_user", "",
                 {"disp_name": "tester", "email": "tester@test.com", "phone_number": "", "pass": "tester_pass"},
                 400, "Bad request")
    testEndpoint("Create user, phone number not string", "POST", "/create_user", "",
                 {"disp_name": "tester", "email": "tester@test.com", "phone_number": ["123456789012345"], "pass": "tester_pass"},
                 400, "Bad request")
    testEndpoint("Create user, phone number is not numeric", "POST", "/create_user", "",
                 {"disp_name": "tester", "email": "tester@test.com", "phone_number": "12345678901234a", "pass": "tester_pass"},
                 400, "Bad request")
    testEndpoint("Create user, phone number length longer than 15", "POST", "/create_user", "",
                 {"disp_name": "tester", "email": "tester@test.com", "phone_number": "1234567890123456", "pass": "tester_pass"},
                 400, "Bad request")
    testEndpoint("Create user, method is not POST", "PATCH", "/create_user", "",
                 {"disp_name": "tester", "email": "tester@test.com", "phone_number": "123456789012345", "pass": "tester_pass"},
                 405, "")
    testEndpoint("Create user", "POST", "/create_user", "",
                 {"disp_name": "tester", "email": "tester@test.com", "phone_number": "123456789012345", "pass": "tester_pass"},
                 201, "")
    testEndpoint("Create user, user already exists", "POST", "/create_user", "",
                 {"disp_name": "tester", "email": "tester@test.com", "phone_number": "123456789012345", "pass": "tester_pass"},
                 400, "User already exists")


def tokens():
    global refresh_jar
    global access_jar
    global wrong_access_jar
    global wrong_refresh_jar
    global reuse_refresh_jar

    refresh_jar = testEndpoint("Get refresh token", "POST", "/token/get_refresh_token", "",
                 {"email": "tester@test.com", "pass": "tester_pass"}, 200, "").cookies
    testEndpoint("Get refresh token, no email", "POST", "/token/get_refresh_token", "",
                 {"pass": "tester_pass"}, 400, "Bad request")
    testEndpoint("Get refresh token, email empty", "POST", "/token/get_refresh_token", "",
                 {"email": "", "pass": "tester_pass"}, 400, "Bad request")
    testEndpoint("Get refresh token, email not string", "POST", "/token/get_refresh_token", "",
                 {"email": ["tester@test.com"], "pass": "tester_pass"}, 400, "Bad request")
    testEndpoint("Get refresh token, email with no @", "POST", "/token/get_refresh_token", "",
                 {"email": "testertest.com", "pass": "tester_pass"}, 400, "Bad request")
    testEndpoint("Get refresh token, no password", "POST", "/token/get_refresh_token", "",
                 {"email": "tester@test.com"}, 400, "Bad request")
    testEndpoint("Get refresh token, password empty", "POST", "/token/get_refresh_token", "",
                 {"email": "tester@test.com", "pass": ""}, 400, "Bad request")
    testEndpoint("Get refresh token, password not string", "POST", "/token/get_refresh_token", "",
                 {"email": "tester@test.com", "pass": ["tester_pass"]}, 400, "Bad request")
    testEndpoint("Get refresh token, password incorrect", "POST", "/token/get_refresh_token", "",
                 {"email": "tester@test.com", "pass": "tester_pass_incorrect"}, 403, "Unauthorised")
    testEndpoint("Get refresh token, method is not POST", "PATCH", "/token/get_refresh_token", "",
                 {"email": "tester@test.com", "pass": "tester_pass"}, 405, "")

    reuse_refresh_jar = refresh_jar.copy()
    refresh_jar = testEndpoint("Refresh refresh token", "POST", "/token/refresh_refresh_token", refresh_jar,
                 {}, 200, "").cookies
    wrong_access_jar = refresh_jar.copy()
    for cookie in wrong_access_jar:
        if cookie.name == 'RefreshToken':
            cookie.name = "AccessToken"
            cookie.path = "/"
            break

    access_jar = testEndpoint("Get access token", "POST", "/token/get_access_token", refresh_jar,
                 {}, 200, "").cookies
    wrong_refresh_jar = access_jar.copy()
    for cookie in wrong_refresh_jar:
        if cookie.name == 'AccessToken':
            cookie.name = "RefreshToken"
            cookie.path = "/"
            break

    testEndpoint("Refresh refresh token, reused refresh token", "POST", "/token/refresh_refresh_token", reuse_refresh_jar,
                 {}, 403, "Unauthorised")
    testEndpoint("Refresh refresh token, wrong refresh token", "POST", "/token/refresh_refresh_token", wrong_refresh_jar,
                 {}, 403, "Unauthorised")
    testEndpoint("Get access token, reused refresh token", "POST", "/token/get_access_token", reuse_refresh_jar,
                 {}, 403, "Unauthorised")
    testEndpoint("Get access token, wrong refresh token", "POST", "/token/get_access_token", wrong_refresh_jar,
                 {}, 403, "Unauthorised")


def changeUserData():
    global access_jar
    global wrong_access_jar

    testEndpoint("Change display name", "POST", "/change_disp_name", access_jar,
                 {"new_disp_name": "testerer"}, 204, "")
    testEndpoint("Change display name, no new display name", "POST", "/change_disp_name", access_jar,
                 {}, 400, "Bad request")
    testEndpoint("Change display name, new display name empty", "POST", "/change_disp_name", access_jar,
                 {"new_disp_name": ""}, 400, "Bad request")
    testEndpoint("Change display name, new display name not string", "POST", "/change_disp_name", access_jar,
                 {"new_disp_name": ["testerer"]}, 400, "Bad request")
    testEndpoint("Change display name, new display name length longer than 200", "POST", "/change_disp_name", access_jar,
                 {"new_disp_name": "testerer" * 25 + "+"}, 400, "Bad request")
    testEndpoint("Change display name, wrong token", "POST", "/change_disp_name", wrong_access_jar,
                 {"new_disp_name": "testerer"}, 403, "Unauthorised")
    testEndpoint("Change display name, no token", "POST", "/change_disp_name", "",
                 {"new_disp_name": "testerer"}, 400, "Bad request")
    testEndpoint("Change display name, method is not POST", "PATCH", "/change_disp_name", access_jar,
                 {"new_disp_name": "testerer"}, 405, "")

    testEndpoint("Change phone number", "POST", "/change_phone_number", access_jar,
                 {"new_phone_number": "12345"}, 204, "")
    testEndpoint("Change phone number, no new phone number", "POST", "/change_phone_number", access_jar,
                 {}, 400, "Bad request")
    testEndpoint("Change phone number, new phone number empty", "POST", "/change_phone_number", access_jar,
                 {"new_phone_number": ""}, 400, "Bad request")
    testEndpoint("Change phone number, new phone number not string", "POST", "/change_phone_number", access_jar,
                 {"new_phone_number": ["12345"]}, 400, "Bad request")
    testEndpoint("Change phone number, new phone number not numeric", "POST", "/change_phone_number", access_jar,
                 {"new_phone_number": "12345a"}, 400, "Bad request")
    testEndpoint("Change phone number, new phone number length longer than 15", "POST", "/change_phone_number", access_jar,
                 {"new_phone_number": "12345" * 3 + "+"}, 400, "Bad request")
    testEndpoint("Change phone number, wrong token", "POST", "/change_phone_number", wrong_access_jar,
                 {"new_phone_number": "12345"}, 403, "Unauthorised")
    testEndpoint("Change phone number, no token", "POST", "/change_phone_number", "",
                 {"new_phone_number": "12345"}, 400, "Bad request")
    testEndpoint("Change phone number, method is not POST", "PATCH", "/change_phone_number", access_jar,
                 {"new_phone_number": "12345"}, 405, "")

    testEndpoint("Change password", "POST", "/change_pass", access_jar,
                 {"new_pass": "tmp_tester_pass"}, 204, "")
    testEndpoint("Change password back", "POST", "/change_pass", access_jar,
                 {"new_pass": "tester_pass"}, 204, "")
    testEndpoint("Change password, no new password", "POST", "/change_pass", access_jar,
                 {}, 400, "Bad request")
    testEndpoint("Change password, new password not string", "POST", "/change_pass", access_jar,
                 {"new_pass": ["tester_pass"]}, 400, "Bad request")
    testEndpoint("Change password, new password length no at least 8", "POST", "/change_pass", access_jar,
                 {"new_pass": "tester_"}, 400, "Bad request")
    testEndpoint("Change password", "POST", "/change_pass", wrong_access_jar,
                 {"new_pass": "tester_pass"}, 403, "Unauthorised")
    testEndpoint("Change password, no token", "POST", "/change_pass", "",
                 {"new_pass": "tester_pass"}, 400, "Bad request")
    testEndpoint("Change password, method is not POST", "PATCH", "/change_pass", access_jar,
                 {"new_pass": "tester_pass"}, 405, "")


def createIntezmeny():
    global access_jar
    global wrong_access_jar

    testEndpoint("Create intezmeny", "POST", "/create_intezmeny", access_jar,
                 {"intezmeny_name": "tester_intezmeny"}, 201, "")
    testEndpoint("Create intezmeny, intezmeny_name not string", "POST", "/create_intezmeny", access_jar,
                 {"intezmeny_name": ["tester_intezmeny"]}, 400, "Bad request")
    testEndpoint("Create intezmeny, no intezmeny_name", "POST", "/create_intezmeny", access_jar,
                 {}, 400, "Bad request")
    testEndpoint("Create intezmeny, intezmeny_name empty", "POST", "/create_intezmeny", access_jar,
                 {"intezmeny_name": ""}, 400, "Bad request")
    testEndpoint("Create intezmeny, intezmeny_name length longer than 200", "POST", "/create_intezmeny", access_jar,
                 {"intezmeny_name": "tester_intezmeny"*16}, 400, "Bad request")
    testEndpoint("Create intezmeny, wrong token", "POST", "/create_intezmeny", wrong_access_jar,
                 {"intezmeny_name": "tester_intezmeny"}, 403, "Unauthorised")
    testEndpoint("Create intezmeny, no token", "POST", "/create_intezmeny", "",
                 {"intezmeny_name": "tester_intezmeny"}, 400, "Bad request")
    testEndpoint("Create intezmeny, method is not POST", "PATCH", "/create_intezmeny", access_jar,
                 {"intezmeny_name": "tester_intezmeny"}, 405, "")


def getEndpoints():
    global access_jar
    global wrong_access_jar
    global intezmeny_id

    response = testEndpointNoErrorHandling("Get intezmenys", "GET", "/get_intezmenys", access_jar, {})
    intezmeny_id = response.json()[0][0]
    handleApiError(response, 200, f"[[{intezmeny_id},\"tester_intezmeny\"]]")
    testEndpoint("Get intezmenys, wrong token", "GET", "/get_intezmenys", wrong_access_jar,
                 {}, 403, "Unauthorised")
    testEndpoint("Get intezmenys, no token", "GET", "/get_intezmenys", "",
                 {}, 400, "Bad request")
    testEndpoint("Get intezmenys, method is not GET", "PATCH", "/get_intezmenys", access_jar,
                 {}, 405, "")

    testEndpoint("Get classes", "POST", "/get_classes", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 200, "[]")
    testEndpoint("Get classes, intezmeny does not exist", "POST", "/get_classes", access_jar,
                 {"intezmeny_id": "347653267853"}, 403, "Unauthorised")
    testEndpoint("Get classes, intezmeny id out of representable range of int", "POST", "/get_classes", access_jar,
                 {"intezmeny_id": "3476532678537834698573463247856326578324685268734578635734278298673426324568325634256"}, 400, "Bad request")
    testEndpoint("Get classes, no intezmeny id", "POST", "/get_classes", access_jar,
                 {}, 400, "Bad request")
    testEndpoint("Get classes, intezmeny id empty", "POST", "/get_classes", access_jar,
                 {"intezmeny_id": ""}, 400, "Bad request")
    testEndpoint("Get classes, intezmeny id is not numeric", "POST", "/get_classes", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}a"}, 400, "Bad request")
    testEndpoint("Get classes, wrong token", "POST", "/get_classes", wrong_access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 403, "Unauthorised")
    testEndpoint("Get classes, no token", "POST", "/get_classes", "",
                 {"intezmeny_id": f"{intezmeny_id}"}, 400, "Bad request")
    testEndpoint("Get classes, method not POST", "PATCH", "/get_classes", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 405, "")

    testEndpoint("Get lessons", "POST", "/get_lessons", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 200, "[]")
    testEndpoint("Get lessons, intezmeny does not exist", "POST", "/get_lessons", access_jar,
                 {"intezmeny_id": "347653267853"}, 403, "Unauthorised")
    testEndpoint("Get lessons, intezmeny id out of representable range of int", "POST", "/get_lessons", access_jar,
                 {"intezmeny_id": "3476532678537834698573463247856326578324685268734578635734278298673426324568325634256"}, 400, "Bad request")
    testEndpoint("Get lessons, no intezmeny id", "POST", "/get_lessons", access_jar,
                 {}, 400, "Bad request")
    testEndpoint("Get lessons, intezmeny id empty", "POST", "/get_lessons", access_jar,
                 {"intezmeny_id": ""}, 400, "Bad request")
    testEndpoint("Get lessons, intezmeny id is not numeric", "POST", "/get_lessons", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}a"}, 400, "Bad request")
    testEndpoint("Get lessons, wrong token", "POST", "/get_lessons", wrong_access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 403, "Unauthorised")
    testEndpoint("Get lessons, no token", "POST", "/get_lessons", "",
                 {"intezmeny_id": f"{intezmeny_id}"}, 400, "Bad request")
    testEndpoint("Get lessons, method not POST", "PATCH", "/get_lessons", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 405, "")

    testEndpoint("Get groups", "POST", "/get_groups", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 200, "[]")
    testEndpoint("Get groups, intezmeny does not exist", "POST", "/get_groups", access_jar,
                 {"intezmeny_id": "347653267853"}, 403, "Unauthorised")
    testEndpoint("Get groups, intezmeny id out of representable range of int", "POST", "/get_groups", access_jar,
                 {"intezmeny_id": "3476532678537834698573463247856326578324685268734578635734278298673426324568325634256"}, 400, "Bad request")
    testEndpoint("Get groups, no intezmeny id", "POST", "/get_groups", access_jar,
                 {}, 400, "Bad request")
    testEndpoint("Get groups, intezmeny id empty", "POST", "/get_groups", access_jar,
                 {"intezmeny_id": ""}, 400, "Bad request")
    testEndpoint("Get groups, intezmeny id is not numeric", "POST", "/get_groups", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}a"}, 400, "Bad request")
    testEndpoint("Get groups, wrong token", "POST", "/get_groups", wrong_access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 403, "Unauthorised")
    testEndpoint("Get groups, no token", "POST", "/get_groups", "",
                 {"intezmeny_id": f"{intezmeny_id}"}, 400, "Bad request")
    testEndpoint("Get groups, method not POST", "PATCH", "/get_groups", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 405, "")

    testEndpoint("Get rooms", "POST", "/get_rooms", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 200, "[]")
    testEndpoint("Get rooms, intezmeny does not exist", "POST", "/get_rooms", access_jar,
                 {"intezmeny_id": "347653267853"}, 403, "Unauthorised")
    testEndpoint("Get rooms, intezmeny id out of representable range of int", "POST", "/get_rooms", access_jar,
                 {"intezmeny_id": "3476532678537834698573463247856326578324685268734578635734278298673426324568325634256"}, 400, "Bad request")
    testEndpoint("Get rooms, no intezmeny id", "POST", "/get_rooms", access_jar,
                 {}, 400, "Bad request")
    testEndpoint("Get rooms, intezmeny id empty", "POST", "/get_rooms", access_jar,
                 {"intezmeny_id": ""}, 400, "Bad request")
    testEndpoint("Get rooms, intezmeny id is not numeric", "POST", "/get_rooms", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}a"}, 400, "Bad request")
    testEndpoint("Get rooms, wrong token", "POST", "/get_rooms", wrong_access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 403, "Unauthorised")
    testEndpoint("Get rooms, no token", "POST", "/get_rooms", "",
                 {"intezmeny_id": f"{intezmeny_id}"}, 400, "Bad request")
    testEndpoint("Get rooms, method not POST", "PATCH", "/get_rooms", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 405, "")

    testEndpoint("Get teachers", "POST", "/get_teachers", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 200, "[]")
    testEndpoint("Get teachers, intezmeny does not exist", "POST", "/get_teachers", access_jar,
                 {"intezmeny_id": "347653267853"}, 403, "Unauthorised")
    testEndpoint("Get teachers, intezmeny id out of representable range of int", "POST", "/get_teachers", access_jar,
                 {"intezmeny_id": "3476532678537834698573463247856326578324685268734578635734278298673426324568325634256"}, 400, "Bad request")
    testEndpoint("Get teachers, no intezmeny id", "POST", "/get_teachers", access_jar,
                 {}, 400, "Bad request")
    testEndpoint("Get teachers, intezmeny id empty", "POST", "/get_teachers", access_jar,
                 {"intezmeny_id": ""}, 400, "Bad request")
    testEndpoint("Get teachers, intezmeny id is not numeric", "POST", "/get_teachers", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}a"}, 400, "Bad request")
    testEndpoint("Get teachers, wrong token", "POST", "/get_teachers", wrong_access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 403, "Unauthorised")
    testEndpoint("Get teachers, no token", "POST", "/get_teachers", "",
                 {"intezmeny_id": f"{intezmeny_id}"}, 400, "Bad request")
    testEndpoint("Get teachers, method not POST", "PATCH", "/get_teachers", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 405, "")

    testEndpoint("Get timetable", "POST", "/get_timetable", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 200, "[]")
    testEndpoint("Get timetable, intezmeny does not exist", "POST", "/get_timetable", access_jar,
                 {"intezmeny_id": "347653267853"}, 403, "Unauthorised")
    testEndpoint("Get timetable, intezmeny id out of representable range of int", "POST", "/get_timetable", access_jar,
                 {"intezmeny_id": "3476532678537834698573463247856326578324685268734578635734278298673426324568325634256"}, 400, "Bad request")
    testEndpoint("Get timetable, no intezmeny id", "POST", "/get_timetable", access_jar,
                 {}, 400, "Bad request")
    testEndpoint("Get timetable, intezmeny id empty", "POST", "/get_timetable", access_jar,
                 {"intezmeny_id": ""}, 400, "Bad request")
    testEndpoint("Get timetable, intezmeny id is not numeric", "POST", "/get_timetable", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}a"}, 400, "Bad request")
    testEndpoint("Get timetable, wrong token", "POST", "/get_timetable", wrong_access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 403, "Unauthorised")
    testEndpoint("Get timetable, no token", "POST", "/get_timetable", "",
                 {"intezmeny_id": f"{intezmeny_id}"}, 400, "Bad request")
    testEndpoint("Get timetable, method not POST", "PATCH", "/get_timetable", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 405, "")

    testEndpoint("Get homeworks", "POST", "/get_homeworks", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 200, "[]")
    testEndpoint("Get homeworks, intezmeny does not exist", "POST", "/get_homeworks", access_jar,
                 {"intezmeny_id": "347653267853"}, 403, "Unauthorised")
    testEndpoint("Get homeworks, intezmeny id out of representable range of int", "POST", "/get_homeworks", access_jar,
                 {"intezmeny_id": "3476532678537834698573463247856326578324685268734578635734278298673426324568325634256"}, 400, "Bad request")
    testEndpoint("Get homeworks, no intezmeny id", "POST", "/get_homeworks", access_jar,
                 {}, 400, "Bad request")
    testEndpoint("Get homeworks, intezmeny id empty", "POST", "/get_homeworks", access_jar,
                 {"intezmeny_id": ""}, 400, "Bad request")
    testEndpoint("Get homeworks, intezmeny id is not numeric", "POST", "/get_homeworks", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}a"}, 400, "Bad request")
    testEndpoint("Get homeworks, wrong token", "POST", "/get_homeworks", wrong_access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 403, "Unauthorised")
    testEndpoint("Get homeworks, no token", "POST", "/get_homeworks", "",
                 {"intezmeny_id": f"{intezmeny_id}"}, 400, "Bad request")
    testEndpoint("Get homeworks, method not POST", "PATCH", "/get_homeworks", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 405, "")

    testEndpoint("Get attachments", "POST", "/get_attachments", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "homework_id": "0"}, 200, "[]")
    testEndpoint("Get attachments, intezmeny does not exist", "POST", "/get_attachments", access_jar,
                 {"intezmeny_id": "347653267853", "homework_id": "0"}, 403, "Unauthorised")
    testEndpoint("Get attachments, intezmeny id out of representable range of int", "POST", "/get_attachments", access_jar,
                 {"intezmeny_id": "34765326785" * 25, "homework_id": "0"}, 400, "Bad request")
    testEndpoint("Get attachments, no intezmeny id", "POST", "/get_attachments", access_jar,
                 {"homework_id": "0"}, 400, "Bad request")
    testEndpoint("Get attachments, intezmeny id empty", "POST", "/get_attachments", access_jar,
                 {"intezmeny_id": "", "homework_id": "0"}, 400, "Bad request")
    testEndpoint("Get attachments, intezmeny id is not numeric", "POST", "/get_attachments", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}a", "homework_id": "0"}, 400, "Bad request")
    testEndpoint("Get attachments, homework does not exist", "POST", "/get_attachments", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "homework_id": "25327823"}, 400, "Bad request")
    testEndpoint("Get attachments, homework id out of representable range of int", "POST", "/get_attachments", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "homework_id": "7259345518397" * 25}, 400, "Bad request")
    testEndpoint("Get attachments, no homework id", "POST", "/get_attachments", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 400, "Bad request")
    testEndpoint("Get attachments, homework id empty", "POST", "/get_attachments", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "homework_id": ""}, 400, "Bad request")
    testEndpoint("Get attachments, intezmeny id is not numeric", "POST", "/get_attachments", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "homework_id": "0a"}, 400, "Bad request")
    testEndpoint("Get attachments, wrong token", "POST", "/get_attachments", wrong_access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "homework_id": "0"}, 403, "Unauthorised")
    testEndpoint("Get attachments, no token", "POST", "/get_attachments", "",
                 {"intezmeny_id": f"{intezmeny_id}", "homework_id": "0"}, 400, "Bad request")
    testEndpoint("Get attachments, method not POST", "PATCH", "/get_attachments", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "homework_id": "0"}, 405, "")


def deleteIntezmeny():            
    global access_jar
    global wrong_access_jar
    global intezmeny_id

    testEndpoint("Delete intezmeny", "DELETE", "/delete_intezmeny", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 204, "")
    testEndpoint("Delete intezmeny, intezmeny does not exist", "DELETE", "/delete_intezmeny", access_jar,
                 {"intezmeny_id": "7832678563"}, 403, "Unauthorised")
    testEndpoint("Delete intezmeny, intezmeny id out of range of int", "DELETE", "/delete_intezmeny", access_jar,
                 {"intezmeny_id": "7832678563" * 10}, 400, "Bad request")
    testEndpoint("Delete intezmeny, no intezmeny id", "DELETE", "/delete_intezmeny", access_jar,
                 {}, 400, "Bad request")
    testEndpoint("Delete intezmeny, intezmeny id empty", "DELETE", "/delete_intezmeny", access_jar,
                 {"intezmeny_id": ""}, 400, "Bad request")
    testEndpoint("Delete intezmeny, intezmeny id is not numeric", "DELETE", "/delete_intezmeny", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}a"}, 400, "Bad request")
    testEndpoint("Delete intezmeny, intezmeny id is not string", "DELETE", "/delete_intezmeny", access_jar,
                 {"intezmeny_id": [f"{intezmeny_id}"]}, 400, "Bad request")
    testEndpoint("Delete intezmeny, wrong token", "DELETE", "/delete_intezmeny", wrong_access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 403, "Unauthorised")
    testEndpoint("Delete intezmeny, no token", "DELETE", "/delete_intezmeny", "",
                 {"intezmeny_id": f"{intezmeny_id}"}, 400, "Bad request")
    testEndpoint("Delete intezmeny, method is not DELETE", "PATCH", "/delete_intezmeny", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 405, "")


def deleteUser():
    global access_jar
    global wrong_access_jar

    testEndpoint("Delete user, wrong token", "DELETE", "/delete_user", wrong_access_jar, {}, 403, "Unauthorised")
    testEndpoint("Delete user, no token", "DELETE", "/delete_user", "", {}, 400, "Bad request")
    testEndpoint("Delete user, method is not DELETE", "PATCH", "/delete_user", access_jar, {}, 405, "")
    testEndpoint("Delete user", "DELETE", "/delete_user", access_jar, {}, 204, "")
    testEndpoint("Delete user, user does not exist", "DELETE", "/delete_user", access_jar, {}, 400, "User does not exist")


def cleanup():
    no_phone_refresh_jar = dict()
    no_phone_access_jar = dict()
    
    no_phone_refresh_jar = testEndpoint("Get refresh token for no phone user", "POST", "/token/get_refresh_token", "",
                 {"email": "tester_no_phone@test.com", "pass": "tester_pass"}, 200, "").cookies
    no_phone_access_jar = testEndpoint("Get access token for no phone user", "POST", "/token/get_access_token", no_phone_refresh_jar, {}, 200, "").cookies
    testEndpoint("Delete no phone number user", "DELETE", "/delete_user", no_phone_access_jar, {}, 204, "")


main()
    
