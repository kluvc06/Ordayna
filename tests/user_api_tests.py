import requests

# https://stackoverflow.com/a/28002687
from requests.packages.urllib3.exceptions import InsecureRequestWarning
requests.packages.urllib3.disable_warnings(InsecureRequestWarning)

def handleApiError(message: str, response: requests.Response, expected_res_code, expected_res_body: str):
    global test_count
    global tests_passed

    test_count += 1
    # Delete line and move cursor to collumn 0
    print("\x1b[2K\x1b[G", end="")

    passed = True
    if (response.status_code != expected_res_code or response.text != expected_res_body):
        text = "\n        ".join(response.text.split("\n"))
        expected_text = "\n        ".join(expected_res_body.split("\n"))
        if (text.__len__() == 0): text = "[No Content]"
        print(f"{test_count:>4} {message}: ❌")
        print(f"        Test failed with status code: {response.status_code}")
        print(f"        Received body: {text}\n        Expected body: {expected_text}")
        passed = False
        if (response.cookies.__len__() != 0 and response.status_code >= 400):
            print("        Endpoint returned cookies!!!")
            passed = False
    else:
        if (response.cookies.__len__() != 0 and response.status_code >= 400):
            print(f"{test_count:>4} {message}: ❌")
            print("        Endpoint returned cookies!!!")
            passed = False

    if (passed): tests_passed += 1
    print(f"Next test: {test_count + 1}", end="", flush=True)

def testEndpoint(message: str, method: str, endpoint_path: str, cookies, payload: dict(), expected_res_code, expected_res_body):
    response = requests.request(method, URL + endpoint_path, json=payload, cookies=cookies, verify=False)
    handleApiError(message, response, expected_res_code, expected_res_body)
    return response

def testEndpointNoErrorHandling(method: str, endpoint_path: str, cookies, payload: dict()):
    return requests.request(method, URL + endpoint_path, json=payload, cookies=cookies, verify=False)

def testId(base_message: str, method: str, endpoint_path: str, base_payload: dict(), jar: dict, id_name: str, null_allowed: bool, success_code: int, is_sensitive: bool):
    if (null_allowed):
        testEndpoint(f"{base_message}, no {id_name}", method, endpoint_path, jar, base_payload, success_code, "")
    else:
        testEndpoint(f"{base_message}, no {id_name}", method, endpoint_path, jar, base_payload, 400, "Bad request")
    base_payload[id_name] = "347653267853"
    if (is_sensitive):
        testEndpoint(f"{base_message}, {id_name} does not exist", method, endpoint_path, jar, base_payload, 403, "Unauthorised")
    else:
        testEndpoint(f"{base_message}, {id_name} does not exist", method, endpoint_path, jar, base_payload, 400, "Bad request")

    base_payload[id_name] = "347653267853" * 25
    testEndpoint(f"{base_message}, {id_name} out of representable range of int", method, endpoint_path, jar, base_payload, 400, "Bad request")
    base_payload[id_name] = ""
    testEndpoint(f"{base_message}, {id_name} id empty", method, endpoint_path, jar, base_payload, 400, "Bad request")
    base_payload[id_name] = ["1"]
    testEndpoint(f"{base_message}, {id_name} is not string", method, endpoint_path, jar, base_payload, 400, "Bad request")
    base_payload[id_name] = "1a"
    testEndpoint(f"{base_message}, {id_name} is not numeric", method, endpoint_path, jar, base_payload, 400, "Bad request")

def testToken(base_message: str, method: str, endpoint_path: str, payload: dict, wrong_jar: dict, reuse_jar=None):
    if (not reuse_jar is None):
        testEndpoint(f"{base_message}, reused token", method, endpoint_path, reuse_jar, payload, 403, "Unauthorised")    
    testEndpoint(f"{base_message}, wrong token", method, endpoint_path, wrong_jar, payload, 403, "Unauthorised")
    testEndpoint(f"{base_message}, no token", method, endpoint_path, "", payload, 400, "Bad request")

def testString(base_message: str, method: str, endpoint_path: str, base_payload: dict, jar: dict, string_name: str, null_allowed: bool, success_code: int):
    if (null_allowed):
        testEndpoint(f"{base_message}, no {string_name}", method, endpoint_path, jar, base_payload, success_code, "")
    else:
        testEndpoint(f"{base_message}, no {string_name}", method, endpoint_path, jar, base_payload, 400, "Bad request")
    base_payload[string_name] = ""
    testEndpoint(f"{base_message}, {string_name} empty", method, endpoint_path, jar, base_payload, 400, "Bad request")
    base_payload[string_name] = [f"test@{string_name}"]
    testEndpoint(f"{base_message}, {string_name} is not string", method, endpoint_path, jar, base_payload, 400, "Bad request")
    # Includes the "@" symbol so that this test works with emails as well
    base_payload[string_name] = f"test@{string_name}" * 300
    testEndpoint(f"{base_message}, {string_name} is too long", method, endpoint_path, jar, base_payload, 400, "Bad request")

def testEmail(base_message: str, method: str, endpoint_path: str, base_payload: dict, jar: dict, null_allowed: bool, success_code: int):
    testString(base_message, method, endpoint_path, base_payload, jar, "email", null_allowed, success_code)
    base_payload["email"] = "testertest.com"
    testEndpoint(f"{base_message}, email with no @", method, endpoint_path, jar, base_payload, 400, "Bad request")

def testPassword(base_message: str, method: str, endpoint_path: str, base_payload: dict, jar: dict, pass_name: str, is_new_pass: bool):
    testString(base_message, method, endpoint_path, base_payload, jar, pass_name, False, 200)
    base_payload[pass_name] = "tester_pass"
    testEndpoint(f"{base_message}, {pass_name} length shorter than 12", method, endpoint_path, jar, base_payload, 400, "Bad request")
    if (not is_new_pass):
        base_payload[pass_name] = "incorrect_tester_pass"
        testEndpoint(f"{base_message}, incorrect {pass_name}", method, endpoint_path, jar, base_payload, 403, "Unauthorised")

def testNumber(base_message: str, method: str, endpoint_path: str, base_payload: dict, jar: dict, number_name: str, null_allowed: bool, success_code: int):
    if (null_allowed):
        testEndpoint(f"{base_message}, no {number_name}", method, endpoint_path, jar, base_payload, success_code, "")
    else:
        testEndpoint(f"{base_message}, no {number_name}", method, endpoint_path, jar, base_payload, 400, "Bad request")
    base_payload[number_name] = ""
    testEndpoint(f"{base_message}, {number_name} empty", method, endpoint_path, jar, base_payload, 400, "Bad request")
    base_payload[number_name] = ["1"]
    testEndpoint(f"{base_message}, {number_name} not string", method, endpoint_path, jar, base_payload, 400, "Bad request")
    base_payload[number_name] = "1a"
    testEndpoint(f"{base_message}, {number_name} is not numeric", method, endpoint_path, jar, base_payload, 400, "Bad request")
    base_payload[number_name] = "1" * 750
    testEndpoint(f"{base_message}, {number_name} is out of int's representable range", method, endpoint_path, jar, base_payload, 400, "Bad request")

def testPhoneNumber(base_message: str, method: str, endpoint_path: str, base_payload: dict, jar: dict, phone_name: str, null_allowed: bool, success_code: int):
    testNumber(base_message, method,endpoint_path, base_payload, jar, phone_name, null_allowed, success_code)
    base_payload[phone_name] = "1234567890123456"
    testEndpoint(f"{base_message}, {phone_name} length longer than 15", method, endpoint_path, jar, base_payload, 400, "Bad request")

def testDateTime(base_message: str, method: str, endpoint_path: str, base_payload: dict, jar: dict, date_name: str, null_allowed: bool, success_code: int, include_date: bool, include_time: bool):
    if (null_allowed):
        testEndpoint(f"{base_message}, no {date_name}", method, endpoint_path, access_jar, base_payload, success_code, "")
    else:
        testEndpoint(f"{base_message}, no {date_name}", method, endpoint_path, access_jar, base_payload, 400, "Bad request")
    base_payload[date_name] = ""
    testEndpoint(f"{base_message}, {date_name} empty", method, endpoint_path, access_jar, base_payload, 400, "Bad request")
    if (include_date and not include_time): base_payload[date_name] = "2025-12-24a"
    if (not include_date and include_time): base_payload[date_name] = "02:02:2"
    if (include_date and include_time): base_payload[date_name] = "2025-12-24 02:2:02"
    testEndpoint(f"{base_message}, invalid {date_name}", method, endpoint_path, access_jar, base_payload, 400, "Bad request")
    if (include_date and not include_time): base_payload[date_name] = "2025-12-24\x00"
    if (not include_date and include_time): base_payload[date_name] = "02:02:02\x00"
    if (include_date and include_time): base_payload[date_name] = "2025-12-24 02:02:02\x00"
    testEndpoint(f"{base_message}, null byte in {date_name}", method, endpoint_path, access_jar, base_payload, 400, "Bad request")
    if (include_date and not include_time): base_payload[date_name] = ["2025-12-24"]
    if (not include_date and include_time): base_payload[date_name] = ["02:02:02"]
    if (include_date and include_time): base_payload[date_name] = ["2025-12-24 02:02:02"]
    base_payload[date_name] = ["2025-12-24"]
    testEndpoint(f"{base_message}, {date_name} not string", method, endpoint_path, access_jar, base_payload, 400, "Bad request")
    if (include_date and not include_time): base_payload[date_name] = "2025-13-24"
    if (not include_date and include_time): base_payload[date_name] = "02:99:02"
    if (include_date and include_time): base_payload[date_name] = "2025-12-24 25:02:02"
    testEndpoint(f"{base_message}, {date_name} overflow", method, endpoint_path, access_jar, base_payload, 400, "Bad request")


test_count = 0
tests_passed = 0
refresh_jar = dict()
access_jar = dict()
wrong_refresh_jar = dict()
reuse_refresh_jar = dict()
wrong_access_jar = dict()
intezmeny_id = 0
teacher_uid = 0
teacher_refresh_jar = dict()
teacher_access_jar = dict()
URL = "https://127.0.0.1:443"

def main():
    global test_count
    global tests_passed

    print(f"Next test: {test_count + 1}", end="", flush=True)
    createUser()
    tokens()
    changeUserData()
    createIntezmeny()
    inviteEndpoints()
    intezmenyCreateEndpoints()
    intezmenyUpdateEndpoints()
    intezmenyGetEndpoints()
    intezmenyDeleteEndpoints()
    deleteIntezmeny()
    deleteUser()

    cleanup()

    # Delete line and move cursor to start of collumn 0
    print("\x1b[2K\x1b[G", end="")
    print(f"Tests passed: {tests_passed}/{test_count}")


def createUser():
    testString("Create user", "POST", "/user/create", {"email": "tester@test.com", "phone_number": "123456789012345", "pass": "tester_pass+"}, {}, "disp_name", False, 201)
    testEmail("Create user", "POST", "/user/create", {"disp_name": "tester", "phone_number": "123456789012345", "pass": "tester_pass+"}, {}, False, 201)
    testPassword("Create user", "POST", "/user/create", {"disp_name": "tester", "email": "tester@test.com", "phone_number": "123456789012345"}, {}, "pass", True)
    testPhoneNumber("Create user", "POST", "/user/create", {"disp_name": "tester", "email": "tester_no_phone@test.com", "pass": "tester_pass+"}, {}, "phone_number", True, 201)
    testEndpoint("Create user, method is not POST", "PATCH", "/user/create", "",
                 {"disp_name": "tester", "email": "tester@test.com", "phone_number": "123456789012345", "pass": "tester_pass+"}, 405, "")
    testEndpoint("Create user", "POST", "/user/create", "",
                 {"disp_name": "tester", "email": "tester@test.com", "phone_number": "123456789012345", "pass": "tester_pass+"}, 201, "")
    testEndpoint("Create user, user already exists", "POST", "/user/create", "",
                 {"disp_name": "tester", "email": "tester@test.com", "phone_number": "123456789012345", "pass": "tester_pass+"}, 400, "Already exists")


def tokens():
    global refresh_jar
    global access_jar
    global wrong_access_jar
    global wrong_refresh_jar
    global reuse_refresh_jar

    refresh_jar = testEndpoint("Get refresh token", "POST", "/token/get_refresh_token", "",
                 {"email": "tester@test.com", "pass": "tester_pass+"}, 200, "").cookies
    testEmail("Get refresh_token", "POST", "/token/get_refresh_token", {"pass": "tester_pass+"}, {}, False, 200)
    testPassword("Get refresh token", "POST", "/token/get_refresh_token", {"email": "tester@test.com"}, {}, "pass", False)
    testEndpoint("Get refresh token, method is not POST", "PATCH", "/token/get_refresh_token", "",
                 {"email": "tester@test.com", "pass": "tester_pass+"}, 405, "")

    reuse_refresh_jar = refresh_jar.copy()
    refresh_jar = testEndpoint("Refresh refresh token", "GET", "/token/refresh_refresh_token", refresh_jar, {}, 200, "").cookies
    wrong_access_jar = refresh_jar.copy()
    for cookie in wrong_access_jar:
        if cookie.name == 'RefreshToken':
            cookie.name = "AccessToken"
            cookie.path = "/"
            break

    access_jar = testEndpoint("Get access token", "GET", "/token/get_access_token", refresh_jar, {}, 200, "").cookies
    wrong_refresh_jar = access_jar.copy()
    for cookie in wrong_refresh_jar:
        if cookie.name == 'AccessToken':
            cookie.name = "RefreshToken"
            cookie.path = "/"
            break

    testToken("Refresh refresh token", "GET", "/token/refresh_refresh_token", {}, wrong_refresh_jar, reuse_refresh_jar)
    testToken("Get access token", "GET", "/token/get_access_token", {}, wrong_refresh_jar, reuse_refresh_jar)


def changeUserData():
    global refresh_jar
    global reuse_refresh_jar
    global wrong_refresh_jar
    global access_jar
    global wrong_access_jar

    testEndpoint("Change display name", "POST", "/user/change/display_name", access_jar, {"new_disp_name": "testerer"}, 204, "")
    testString("Change display name", "POST", "/user/change/display_name", {}, access_jar, "new_disp_name", False, 204)
    testToken("Change display name", "POST", "/user/change/display_name", {"new_disp_name": "testerer"}, wrong_access_jar)
    testEndpoint("Change display name, method is not POST", "PATCH", "/user/change/display_name", access_jar, {"new_disp_name": "testerer"}, 405, "")

    testEndpoint("Change phone number", "POST", "/user/change/phone_number", access_jar, {"new_phone_number": "12345"}, 204, "")
    testPhoneNumber("Change phone number", "POST", "/user/change/phone_number", {}, access_jar, "new_phone_name", False, 204)
    testToken("Change phone number", "POST", "/user/change/phone_number", {"new_phone_number": "12345"}, wrong_access_jar)
    testEndpoint("Change phone number, method is not POST", "PATCH", "/user/change/phone_number", access_jar, {"new_phone_number": "12345"}, 405, "")

    testEndpoint("Change password", "POST", "/user/change/password", access_jar, {"pass": "tester_pass+", "new_pass": "tmp_tester_pass"}, 204, "")
    refresh_jar = testEndpoint("Get refresh token", "POST", "/token/get_refresh_token", "",
                 {"email": "tester@test.com", "pass": "tmp_tester_pass"}, 200, "").cookies
    access_jar = testEndpoint("Get access token", "GET", "/token/get_access_token", refresh_jar, {}, 200, "").cookies
    testEndpoint("Change password back", "POST", "/user/change/password", access_jar, {"pass": "tmp_tester_pass", "new_pass": "tester_pass+"}, 204, "")
    refresh_jar = testEndpoint("Get refresh token", "POST", "/token/get_refresh_token", "",
                 {"email": "tester@test.com", "pass": "tester_pass+"}, 200, "").cookies
    reuse_refresh_jar = refresh_jar.copy()
    refresh_jar = testEndpoint("Refresh refresh token", "GET", "/token/refresh_refresh_token", refresh_jar, {}, 200, "").cookies
    wrong_access_jar = refresh_jar.copy()
    for cookie in wrong_access_jar:
        if cookie.name == 'RefreshToken':
            cookie.name = "AccessToken"
            cookie.path = "/"
            break

    access_jar = testEndpoint("Get access token", "GET", "/token/get_access_token", refresh_jar, {}, 200, "").cookies
    wrong_refresh_jar = access_jar.copy()
    for cookie in wrong_refresh_jar:
        if cookie.name == 'AccessToken':
            cookie.name = "RefreshToken"
            cookie.path = "/"
            break
    testPassword("Change password", "POST", "/user/change/password", {"new_pass": "tester_pass+"}, access_jar, "pass", False)
    testPassword("Change password", "POST", "/user/change/password", {"pass": "tester_pass+"}, access_jar, "new_pass", True)
    testToken("Change password", "POST", "/user/change/password", {"pass": "tester_pass+", "new_pass": "tester_pass+"}, wrong_access_jar)
    testEndpoint("Change password, method is not POST", "PATCH", "/user/change/password", access_jar, {"pass": "tester_pass+", "new_pass": "tester_pass+"}, 405, "")


def createIntezmeny():
    global access_jar
    global wrong_access_jar
    global intezmeny_id

    testEndpoint("Create intezmeny", "POST", "/create_intezmeny", access_jar, {"intezmeny_name": "tester_intezmeny"}, 201, "")
    testString("Create intezmeny", "POST", "/create_intezmeny", {}, access_jar, "intezmeny_name", False, 201)
    testToken("Create intezmeny", "POST", "/create_intezmeny", {"intezmeny_name": "tester_intezmeny"}, wrong_access_jar)
    testEndpoint("Create intezmeny, method is not POST", "PATCH", "/create_intezmeny", access_jar, {"intezmeny_name": "tester_intezmeny"}, 405, "")

    response = testEndpointNoErrorHandling("GET", "/get_intezmenys", access_jar, {})
    intezmeny_id = response.json()[len(response.json()) - 1][0]
    handleApiError("Get intezmenys", response, 200, f"[[{intezmeny_id},\"tester_intezmeny\"]]")
    testToken("Get intezmenys", "GET", "/get_intezmenys", {}, wrong_access_jar)
    testEndpoint("Get intezmenys, method is not GET", "PATCH", "/get_intezmenys", access_jar, {}, 405, "")


def inviteEndpoints():
    global teacher_access_jar
    global teacher_refresh_jar
    global teacher_uid
    global access_jar
    global wrong_access_jar
    global intezmeny_id

    testEndpoint("Create teacher user", "POST", "/user/create", {},
                 {"disp_name": "tester", "email": "tester_teacher@test.com", "phone_number": "123456789012345", "pass": "tester_pass+"}, 201, "")
    teacher_refresh_jar = testEndpoint("Get teacher refresh token", "POST", "/token/get_refresh_token", {},
                 {"email": "tester_teacher@test.com", "pass": "tester_pass+"}, 200, "").cookies
    teacher_access_jar = testEndpoint("Get teacher access token", "GET", "/token/get_access_token", teacher_refresh_jar, {}, 200, "").cookies

    testId("Invite user", "POST", "/intezmeny/user/invite", {"email": "tester_teacher@test.com"}, access_jar, "intezmeny_id", False, 200, True)
    testEmail("Invite user", "POST", "/intezmeny/user/invite", {"intezmeny_id": f"{intezmeny_id}"}, access_jar, False, 200)
    testToken("Invite user", "POST", "/intezmeny/user/invite", {"intezmeny_id": f"{intezmeny_id}", "email": "tester_teacher@test.com"}, wrong_access_jar)
    testEndpoint("Invite user, method is not POST", "PATCH", "/intezmeny/user/invite", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "email": "tester_teacher@test.com"}, 405, "")
    testEndpoint("Invite user", "POST", "/intezmeny/user/invite", access_jar, {"intezmeny_id": f"{intezmeny_id}", "email": "tester_teacher@test.com"}, 200, "")
    testEndpoint("Invite user, already invited", "POST", "/intezmeny/user/invite", access_jar, {"intezmeny_id": f"{intezmeny_id}", "email": "tester_teacher@test.com"}, 400, "Already exists")

    testId("Accept invite", "POST", "/intezmeny/user/accept_invite", {}, teacher_access_jar, "intezmeny_id", False, 200, True)
    testToken("Accept invite", "POST", "/intezmeny/user/accept_invite", {"intezmeny_id": f"{intezmeny_id}"}, wrong_access_jar)
    testEndpoint("Accept invite, method is not POST", "PATCH", "/intezmeny/user/accept_invite", teacher_access_jar, {"intezmeny_id": f"{intezmeny_id}"}, 405, "")
    testEndpoint("Accept invite", "POST", "/intezmeny/user/accept_invite", teacher_access_jar, {"intezmeny_id": f"{intezmeny_id}"}, 200, "")
    testEndpoint("Accept invite, already accepted", "POST", "/intezmeny/user/accept_invite", teacher_access_jar, {"intezmeny_id": f"{intezmeny_id}"}, 403, "Unauthorised")

    testToken("Get profile", "GET", "/user/profile", {}, wrong_access_jar)
    testEndpoint("Get profile, method is not GET", "PATCH", "/user/profile", teacher_access_jar, {}, 405, "")
    response = testEndpointNoErrorHandling("GET", "/user/profile", teacher_access_jar, {})
    handleApiError("Get profile", response, 200, f"[{response.json()[0]},\"tester\",\"tester_teacher@test.com\",\"123456789012345\"]")
    teacher_uid = response.json()[0]


def intezmenyCreateEndpoints():
    global access_jar
    global wrong_access_jar
    global intezmeny_id
    global teacher_uid

    testId("Create class", "POST", "/intezmeny/create/class", {"name": "test_class", "headcount": "30"}, access_jar, "intezmeny_id", False, 201, True)
    testString("Create class", "POST", "/intezmeny/create/class", {"intezmeny_id": f"{intezmeny_id}", "headcount": "30"}, access_jar, "name", False, 201)
    testNumber("Create class", "POST", "/intezmeny/create/class", {"intezmeny_id": f"{intezmeny_id}", "name": "test_class"}, access_jar, "headcount", False, 201)
    testToken("Create class", "POST", "/intezmeny/create/class",
              {"intezmeny_id": f"{intezmeny_id}", "name": "test_class", "headcount": "30"}, wrong_access_jar)
    testEndpoint("Create class, method not POST", "PATCH", "/intezmeny/create/class", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "name": "test_class", "headcount": "30"}, 405, "")
    testEndpoint("Create class", "POST", "/intezmeny/create/class", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "name": "test_class", "headcount": "30"}, 201, "")
    testEndpoint("Create class, already exists", "POST", "/intezmeny/create/class", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "name": "test_class", "headcount": "30"}, 400, "Bad request")

    testId("Create lesson", "POST", "/intezmeny/create/lesson", {"name": "test_lesson"}, access_jar, "intezmeny_id", False, 201, True)
    testString("Create lesson", "POST", "/intezmeny/create/lesson", {"intezmeny_id": f"{intezmeny_id}"}, access_jar, "name", False, 201)
    testToken("Create lesson", "POST", "/intezmeny/create/lesson",
              {"intezmeny_id": f"{intezmeny_id}", "name": "test_lesson"}, wrong_access_jar)
    testEndpoint("Create lesson, method not POST", "PATCH", "/intezmeny/create/lesson", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "name": "test_lesson"}, 405, "")
    testEndpoint("Create lesson", "POST", "/intezmeny/create/lesson", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "name": "test_lesson"}, 201, "")
    testEndpoint("Create lesson, already exists", "POST", "/intezmeny/create/lesson", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "name": "test_lesson"}, 400, "Bad request")

    testId("Create group", "POST", "/intezmeny/create/group", {"name": "test_group", "headcount": "30", "class_id": "1"}, access_jar, "intezmeny_id", False, 201, True)
    testString("Create group", "POST", "/intezmeny/create/group", {"intezmeny_id": f"{intezmeny_id}", "headcount": "30", "class_id": "1"}, access_jar, "name", False, 201)
    testNumber("Create group", "POST", "/intezmeny/create/group", {"intezmeny_id": f"{intezmeny_id}", "name": "test_group", "class_id": "1"}, access_jar, "headcount", False, 201)
    testId("Create group", "POST", "/intezmeny/create/group", {"intezmeny_id": f"{intezmeny_id}", "name": "test_group_no_class_id", "headcount": "30"}, access_jar, "class_id", True, 201, False)
    testToken("Create group", "POST", "/intezmeny/create/group",
              {"intezmeny_id": f"{intezmeny_id}", "name": "test_group", "headcount": "30", "class_id": "1"}, wrong_access_jar)
    testEndpoint("Create group, method not POST", "PATCH", "/intezmeny/create/group", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "name": "test_group", "headcount": "30", "class_id": "1"}, 405, "")
    testEndpoint("Create group", "POST", "/intezmeny/create/group", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "name": "test_group", "headcount": "30", "class_id": "1"}, 201, "")
    testEndpoint("Create group, group already exists", "POST", "/intezmeny/create/group", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "name": "test_group", "headcount": "30", "class_id": "1"}, 400, "Bad request")

    testId("Create room", "POST", "/intezmeny/create/room", {"type": "test", "name": "test_room", "space": "30"}, access_jar, "intezmeny_id", False, 201, True)
    testString("Create room", "POST", "/intezmeny/create/room", {"intezmeny_id": f"{intezmeny_id}", "name": "test_room_no_type", "space": "30"}, access_jar, "type", True, 201)
    testString("Create room", "POST", "/intezmeny/create/room", {"intezmeny_id": f"{intezmeny_id}", "type": "test", "space": "30"}, access_jar, "name", False, 201)
    testNumber("Create room", "POST", "/intezmeny/create/room", {"intezmeny_id": f"{intezmeny_id}", "type": "test", "name": "test_room"}, access_jar, "space", False, 201)
    testToken("Create room", "POST", "/intezmeny/create/room",
              {"intezmeny_id": f"{intezmeny_id}", "type": "test", "name": "test_room", "space": "30"}, wrong_access_jar)
    testEndpoint("Create room, method not POST", "PATCH", "/intezmeny/create/room", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "type": "test", "name": "test_room", "space": "30"}, 405, "")
    testEndpoint("Create room", "POST", "/intezmeny/create/room", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "type": "test", "name": "test_room", "space": "30"}, 201, "")
    testEndpoint("Create room, room already exists", "POST", "/intezmeny/create/room", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "type": "test", "name": "test_room", "space": "30"}, 400, "Bad request")

    testId("Create teacher", "POST", "/intezmeny/create/teacher",
           {"name": "test_teacher", "job": "test", "teacher_uid": f"{teacher_uid}"},
           access_jar, "intezmeny_id", False, 201, True)
    testString("Create teacher", "POST", "/intezmeny/create/teacher",
               {"intezmeny_id": f"{intezmeny_id}", "job": "test", "teacher_uid": f"{teacher_uid}"},
               access_jar, "name", False, 201)
    testString("Create teacher", "POST", "/intezmeny/create/teacher",
               {"intezmeny_id": f"{intezmeny_id}", "name": "test_teacher", "teacher_uid": f"{teacher_uid}"},
               access_jar, "job", False, 201)
    testId("Create teacher", "POST", "/intezmeny/create/teacher",
           {"intezmeny_id": f"{intezmeny_id}", "name": "test_teacher_no_user", "job": "test"},
           access_jar, "teacher_uid", True, 201, True)
    testToken("Create teacher", "POST", "/intezmeny/create/teacher",
              {"intezmeny_id": f"{intezmeny_id}", "name": "test_teacher", "job": "test", "teacher_uid": f"{teacher_uid}"},
              wrong_access_jar)
    testEndpoint("Create teacher, method not POST", "PATCH", "/intezmeny/create/teacher", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "name": "test_teacher", "job": "test", "teacher_uid": f"{teacher_uid}"},
                 405, "")
    testEndpoint("Create teacher", "POST", "/intezmeny/create/teacher", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "name": "test_teacher", "job": "test", "teacher_uid": f"{teacher_uid}"},
                 201, "")
    testEndpoint("Create teacher, user already assigned as teacher", "POST", "/intezmeny/create/teacher", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "name": "test_teacher", "job": "test", "teacher_uid": f"{teacher_uid}"},
                 400, "Bad request")


    testId("Create timetable element", "POST", "/intezmeny/create/timetable_element",
           {"duration": "02:02:02", "day": "4", "from": "2020-12-24", "until": "2020-12-25",
            "group_id": "1", "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, access_jar, "intezmeny_id", False, 201, True)
    testDateTime("Create timetable element", "POST", "/intezmeny/create/timetable_element",
                 {"intezmeny_id": f"{intezmeny_id}", "day": "4", "from": "2020-12-24", "until": "2020-12-25",
                  "group_id": "1", "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, access_jar, "duration", False, 201, False, True)
    testNumber("Create timetable element", "POST", "/intezmeny/create/timetable_element",
                 {"intezmeny_id": f"{intezmeny_id}", "duration": "02:02:02", "from": "2020-12-24", "until": "2020-12-25",
                  "group_id": "1", "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, access_jar, "day", False, 201)
    testDateTime("Create timetable element", "POST", "/intezmeny/create/timetable_element",
                 {"intezmeny_id": f"{intezmeny_id}", "duration": "02:02:02", "day": "4", "until": "2020-12-25",
                  "group_id": "1", "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, access_jar, "from", False, 201, True, False)
    testDateTime("Create timetable element", "POST", "/intezmeny/create/timetable_element",
                 {"intezmeny_id": f"{intezmeny_id}", "duration": "02:02:02", "day": "4", "from": "2020-12-24",
                  "group_id": "1", "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, access_jar, "until", False, 201, True, False)
    testId("Create timetable element", "POST", "/intezmeny/create/timetable_element",
           {"intezmeny_id": f"{intezmeny_id}", "duration": "02:02:02", "day": "4", "from": "2020-12-24", "until": "2020-12-25",
            "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, access_jar, "group_id", True, 201, False)
    testId("Create timetable element", "POST", "/intezmeny/create/timetable_element",
           {"intezmeny_id": f"{intezmeny_id}", "duration": "02:02:02", "day": "4", "from": "2020-12-24", "until": "2020-12-25",
            "group_id": "1", "teacher_id": "1", "room_id": "1"}, access_jar, "lesson_id", True, 201, False)
    testId("Create timetable element", "POST", "/intezmeny/create/timetable_element",
           {"intezmeny_id": f"{intezmeny_id}", "duration": "02:02:02", "day": "4", "from": "2020-12-24", "until": "2020-12-25",
            "group_id": "1", "lesson_id": "1", "room_id": "1"}, access_jar, "teacher_id", True, 201, False)
    testId("Create timetable element", "POST", "/intezmeny/create/timetable_element",
           {"intezmeny_id": f"{intezmeny_id}", "duration": "02:02:02", "day": "4", "from": "2020-12-24", "until": "2020-12-25",
            "group_id": "1", "lesson_id": "1", "teacher_id": "1"}, access_jar, "room_id", True, 201, False)
    testEndpoint("Create timetable element, until is before from", "POST", "/intezmeny/create/timetable_element", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "duration": "02:02:02", "day": "4", "from": "2020-12-24", "until": "2020-12-23",
                  "group_id": "1", "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, 400, "Bad request")
    testEndpoint("Create timetable element, until is the same day as from", "POST", "/intezmeny/create/timetable_element", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "duration": "02:02:02", "day": "4", "from": "2020-12-24", "until": "2020-12-24",
                  "group_id": "1", "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, 201, "")
    testToken("Create timetable element", "POST", "/intezmeny/create/timetable_element",
              {"intezmeny_id": f"{intezmeny_id}", "duration": "02:02:02", "day": "4", "from": "2020-12-24", "until": "2020-12-25",
               "group_id": "1", "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, wrong_access_jar)
    testEndpoint("Create timetable element, method is not POST", "PATCH", "/intezmeny/create/timetable_element", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "duration": "02:02:02", "day": "4", "from": "2020-12-24", "until": "2020-12-25",
                  "group_id": "1", "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, 405, "")
    testEndpoint("Create timetable element", "POST", "/intezmeny/create/timetable_element", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "duration": "02:02:02", "day": "4", "from": "2020-12-24", "until": "2020-12-25",
                  "group_id": "1", "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, 201, "")

    testEndpoint("Create homework", "POST", "/intezmeny/create/homework", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "due": "2020-12-24 02:02:02", "lesson_id": "1", "teacher_id": "1"},
                 201, "")
    testEndpoint("Create homework, already exists", "POST", "/intezmeny/create/homework", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "due": "2020-12-24 02:02:02", "lesson_id": "1", "teacher_id": "1"},
                 201, "")
    testId("Create homework", "POST", "/intezmeny/create/homework",
           {"due": "2020-12-24 02:02:02", "lesson_id": "1", "teacher_id": "1"}, access_jar, "intezmeny_id", False, 201, True)
    testDateTime("Create homework", "POST", "/intezmeny/create/homework", {"intezmeny_id": f"{intezmeny_id}", "lesson_id": "1", "teacher_id": "1"},
                 access_jar, "due", True, 201, True, True)
    testId("Create homework", "POST", "/intezmeny/create/homework",
           {"intezmeny_id": f"{intezmeny_id}", "due": "2020-12-24 02:02:02", "teacher_id": "1"}, access_jar, "lesson_id", True, 201, False)
    testId("Create homework", "POST", "/intezmeny/create/homework",
           {"intezmeny_id": f"{intezmeny_id}", "due": "2020-12-24 02:02:02", "lesson_id": "1"}, access_jar, "teacher_id", True, 201, False)
    testToken("Create homework", "POST", "/intezmeny/create/homework",
              {"intezmeny_id": f"{intezmeny_id}", "due": "2020-12-24 02:02:02", "lesson_id": "1", "teacher_id": "1"}, wrong_access_jar)
    testEndpoint("Create homework, method is not POST", "PATCH", "/intezmeny/create/homework", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "due": "2020-12-24 02:02:02", "lesson_id": "1", "teacher_id": "1"},
                 405, "")
    
    testEndpoint("Create attachment", "POST", "/intezmeny/create/attachment", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "homework_id": "1", "file_name": "test_file", "file_contents": "test_text test_text\ntest_text"},
                 201, "")
    testId("Create attachment", "POST", "/intezmeny/create/attachment",
           {"homework_id": "1", "file_name": "test_file", "file_contents": "test_text test_text\ntest_text"}, access_jar, "intezmeny_id", False, 201, True)
    testId("Create attachment", "POST", "/intezmeny/create/attachment",
           {"intezmeny_id": f"{intezmeny_id}", "file_name": "test_file", "file_contents": "test_text test_text\ntest_text"}, access_jar, "homework_id", False, 201, True)
    testString("Create attachment", "POST", "/intezmeny/create/attachment",
               {"intezmeny_id": f"{intezmeny_id}", "homework_id": "1", "file_contents": "test_text test_text\ntest_text"}, access_jar, "file_name", False, 201)
    testEndpoint("Create attachment, file name with illegal character", "POST", "/intezmeny/create/attachment", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "homework_id": "1", "file_name": "test_file\x00", "file_contents": "test_text test_text\ntest_text"},
                 400, "Bad request")
    testEndpoint("Create attachment, no file contents", "POST", "/intezmeny/create/attachment", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "homework_id": "1", "file_name": "test_file"},
                 400, "Bad request")
    testEndpoint("Create attachment, file contents empty", "POST", "/intezmeny/create/attachment", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "homework_id": "1", "file_name": "test_file", "file_contents": ""},
                 201, "")
    testEndpoint("Create attachment, file contents is not string", "POST", "/intezmeny/create/attachment", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "homework_id": "1", "file_name": "test_file", "file_contents": ["test_text test_text\ntest_text"]},
                 400, "Bad request")
    testEndpoint("Create attachment, file contents too long", "POST", "/intezmeny/create/attachment", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "homework_id": "1", "file_name": "test_file", "file_contents": "t" * 1024 * 1024 * 20 + "+"},
                 413, "<html>\r\n<head><title>413 Request Entity Too Large</title></head>\r\n<body>\r\n<center><h1>413 Request Entity Too Large</h1></center>\r\n<hr><center>nginx</center>\r\n</body>\r\n</html>\r\n")
    testEndpoint("Create attachment, file contents with null character", "POST", "/intezmeny/create/attachment", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "homework_id": "1", "file_name": "test_file", "file_contents": "test_text test_text\ntest_text\x00"},
                 201, "")
    testToken("Create attachment", "POST", "/intezmeny/create/attachment",
              {"intezmeny_id": f"{intezmeny_id}", "homework_id": "1", "file_name": "test_file", "file_contents": "test_text test_text\ntest_text"}, wrong_access_jar)
    testEndpoint("Create attachment, method is not POST", "PATCH", "/intezmeny/create/attachment", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "homework_id": "1", "file_name": "test_file", "file_contents": "test_text test_text\ntest_text"},
                 405, "")


def intezmenyUpdateEndpoints():
    global access_jar
    global wrong_access_jar
    global intezmeny_id
    global teacher_uid

    testId("Update class", "POST", "/intezmeny/update/class", {"class_id": "1", "name": "test_class_updated"}, access_jar, "intezmeny_id", False, 204, True)
    testId("Update class", "POST", "/intezmeny/update/class", {"intezmeny_id": f"{intezmeny_id}", "name": "test_class_updated"}, access_jar, "class_id", False, 204, False)
    testString("Update class", "POST", "/intezmeny/update/class", {"intezmeny_id": f"{intezmeny_id}", "class_id": "1"}, access_jar, "name", False, 204)
    testToken("Update class", "POST", "/intezmeny/update/class",
              {"intezmeny_id": f"{intezmeny_id}", "class_id": "1", "name": "test_class_updated"}, wrong_access_jar)
    testEndpoint("Update class, method not POST", "PATCH", "/intezmeny/update/class", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "class_id": "1", "name": "test_class_updated"}, 405, "")
    testEndpoint("Update class", "POST", "/intezmeny/update/class", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "class_id": "1", "name": "test_class_updated"}, 204, "")

    testId("Update lesson", "POST", "/intezmeny/update/lesson", {"lesson_id": "1", "name": "test_lesson_updated"}, access_jar, "intezmeny_id", False, 204, True)
    testId("Update lesson", "POST", "/intezmeny/update/lesson", {"intezmeny_id": f"{intezmeny_id}", "name": "test_lesson_updated"}, access_jar, "lesson_id", False, 204, False)
    testString("Update lesson", "POST", "/intezmeny/update/lesson", {"intezmeny_id": f"{intezmeny_id}", "lesson_id": "1"}, access_jar, "name", False, 204)
    testToken("Update lesson", "POST", "/intezmeny/update/lesson",
              {"intezmeny_id": f"{intezmeny_id}", "lesson_id": "1", "name": "test_lesson_updated"}, wrong_access_jar)
    testEndpoint("Update lesson, method not POST", "PATCH", "/intezmeny/update/lesson", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "lesson_id": "1", "name": "test_lesson_updated"}, 405, "")
    testEndpoint("Update lesson", "POST", "/intezmeny/update/lesson", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "lesson_id": "1", "name": "test_lesson_updated"}, 204, "")

    testId("Update group", "POST", "/intezmeny/update/group",
           {"group_id": "1", "name": "test_group_updated", "headcount": "40", "class_id": "1"}, access_jar, "intezmeny_id", False, 204, True)
    testId("Update group", "POST", "/intezmeny/update/group",
           {"intezmeny_id": f"{intezmeny_id}", "name": "test_group_updated", "headcount": "40", "class_id": "1"}, access_jar, "group_id", False, 204, False)
    testString("Update group", "POST", "/intezmeny/update/group",
               {"intezmeny_id": f"{intezmeny_id}", "group_id": "1", "headcount": "40", "class_id": "1"}, access_jar, "name", False, 204)
    testNumber("Update group", "POST", "/intezmeny/update/group",
               {"intezmeny_id": f"{intezmeny_id}", "group_id": "1", "name": "test_group", "class_id": "1"}, access_jar, "headcount", False, 204)
    testId("Update group", "POST", "/intezmeny/update/group",
           {"intezmeny_id": f"{intezmeny_id}", "group_id": "2", "name": "test_group_updated_no_class_id", "headcount": "40"}, access_jar, "class_id", True, 204, False)
    testToken("Update group", "POST", "/intezmeny/update/group",
              {"intezmeny_id": f"{intezmeny_id}", "group_id": "1", "name": "test_group_updated", "headcount": "40", "class_id": "1"}, wrong_access_jar)
    testEndpoint("Update group, method not POST", "PATCH", "/intezmeny/update/group", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "group_id": "1", "name": "test_group_updated", "headcount": "40", "class_id": "1"}, 405, "")
    testEndpoint("Update group", "POST", "/intezmeny/update/group", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "group_id": "1", "name": "test_group_updated", "headcount": "40", "class_id": "1"}, 204, "")

    testId("Update room", "POST", "/intezmeny/update/room",
           {"room_id": "1", "type": "test_updated", "name": "test_room_updated", "space": "40"}, access_jar, "intezmeny_id", False, 204, True)
    testId("Update room", "POST", "/intezmeny/update/room",
           {"intezmeny_id": f"{intezmeny_id}", "type": "test_updated", "name": "test_room_updated", "space": "40"}, access_jar, "room_id", False, 204, False)
    testString("Update room", "POST", "/intezmeny/update/room",
               {"intezmeny_id": f"{intezmeny_id}", "room_id": "2", "name": "test_room_updated_no_type", "space": "40"}, access_jar, "type", True, 204)
    testString("Update room", "POST", "/intezmeny/update/room",
               {"intezmeny_id": f"{intezmeny_id}", "room_id": "1", "type": "test_updated", "space": "40"}, access_jar, "name", False, 204)
    testNumber("Update room", "POST", "/intezmeny/update/room",
               {"intezmeny_id": f"{intezmeny_id}", "room_id": "1", "type": "test_updated", "name": "test_room"}, access_jar, "space", False, 204)
    testToken("Update room", "POST", "/intezmeny/update/room",
              {"intezmeny_id": f"{intezmeny_id}", "room_id": "1", "type": "test_updated", "name": "test_room_updated", "space": "40"}, wrong_access_jar)
    testEndpoint("Update room, method not POST", "PATCH", "/intezmeny/update/room", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "room_id": "1", "type": "test_updated", "name": "test_room_updated", "space": "40"}, 405, "")
    testEndpoint("Update room", "POST", "/intezmeny/update/room", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "room_id": "1", "type": "test_updated", "name": "test_room_updated", "space": "40"}, 204, "")

    testId("Update teacher", "POST", "/intezmeny/update/teacher",
           {"teacher_id": "2", "name": "test_teacher_updated", "job": "test_updated", "teacher_uid": f"{teacher_uid}"},
           access_jar, "intezmeny_id", False, 204, True)
    testId("Update teacher", "POST", "/intezmeny/update/teacher",
           {"intezmeny_id": f"{intezmeny_id}", "name": "test_teacher_updated", "job": "test_updated", "teacher_uid": f"{teacher_uid}"},
           access_jar, "teacher_id", False, 204, False)
    testString("Update teacher", "POST", "/intezmeny/update/teacher",
               {"intezmeny_id": f"{intezmeny_id}", "teacher_id": "2", "job": "test_updated", "teacher_uid": f"{teacher_uid}"},
               access_jar, "name", False, 204)
    testString("Update teacher", "POST", "/intezmeny/update/teacher",
               {"intezmeny_id": f"{intezmeny_id}", "teacher_id": "2", "name": "test_teacher_updated", "teacher_uid": f"{teacher_uid}"},
               access_jar, "job", False, 204)
    testId("Update teacher", "POST", "/intezmeny/update/teacher",
           {"intezmeny_id": f"{intezmeny_id}", "teacher_id": "1", "name": "test_teacher_updated_no_user", "job": "test_updated"},
           access_jar, "teacher_uid", True, 204, True)
    testToken("Update teacher", "POST", "/intezmeny/update/teacher",
              {"intezmeny_id": f"{intezmeny_id}", "teacher_id": "2", "name": "test_teacher_updated", "job": "test_updated", "teacher_uid": f"{teacher_uid}"},
              wrong_access_jar)
    testEndpoint("Update teacher, method not POST", "PATCH", "/intezmeny/update/teacher", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "teacher_id": "2", "name": "test_teacher_updated", "job": "test_updated", "teacher_uid": f"{teacher_uid}"},
                 405, "")
    testEndpoint("Update teacher", "POST", "/intezmeny/update/teacher", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "teacher_id": "2", "name": "test_teacher_updated", "job": "test_updated", "teacher_uid": f"{teacher_uid}"},
                 204, "")
    testEndpoint("Update teacher, teacher user already assigned", "POST", "/intezmeny/update/teacher", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "teacher_id": "1", "name": "test_teacher_updated", "job": "test_updated", "teacher_uid": f"{teacher_uid}"},
                 400, "Bad request")


    testId("Update timetable element", "POST", "/intezmeny/update/timetable_element",
           {"element_id": "1", "duration": "03:03:03", "day": "5", "from": "2021-11-23", "until": "2021-11-24",
            "group_id": "1", "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, access_jar, "intezmeny_id", False, 204, True)
    testId("Update timetable element", "POST", "/intezmeny/update/timetable_element",
           {"intezmeny_id": f"{intezmeny_id}", "duration": "03:03:03", "day": "5", "from": "2021-11-23", "until": "2021-11-24",
            "group_id": "1", "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, access_jar, "element_id", False, 204, False)
    testDateTime("Update timetable element", "POST", "/intezmeny/update/timetable_element",
                 {"intezmeny_id": f"{intezmeny_id}", "element_id": "1", "day": "5", "from": "2021-11-23", "until": "2021-11-24",
                  "group_id": "1", "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, access_jar, "duration", False, 204, False, True)
    testNumber("Update timetable element", "POST", "/intezmeny/update/timetable_element",
                 {"intezmeny_id": f"{intezmeny_id}", "element_id": "1", "duration": "03:03:03", "from": "2021-11-23", "until": "2021-11-24",
                  "group_id": "1", "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, access_jar, "day", False, 204)
    testDateTime("Update timetable element", "POST", "/intezmeny/update/timetable_element",
                 {"intezmeny_id": f"{intezmeny_id}", "element_id": "1", "duration": "03:03:03", "day": "5", "until": "2021-11-24",
                  "group_id": "1", "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, access_jar, "from", False, 204, True, False)
    testDateTime("Update timetable element", "POST", "/intezmeny/update/timetable_element",
                 {"intezmeny_id": f"{intezmeny_id}", "element_id": "1", "duration": "03:03:03", "day": "5", "from": "2021-11-23",
                  "group_id": "1", "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, access_jar, "until", False, 204, True, False)
    testId("Update timetable element", "POST", "/intezmeny/update/timetable_element",
           {"intezmeny_id": f"{intezmeny_id}", "element_id": "2", "duration": "03:03:03", "day": "5", "from": "2021-11-23", "until": "2021-11-24",
            "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, access_jar, "group_id", True, 204, False)
    testId("Update timetable element", "POST", "/intezmeny/update/timetable_element",
           {"intezmeny_id": f"{intezmeny_id}", "element_id": "3", "duration": "03:03:03", "day": "5", "from": "2021-11-23", "until": "2021-11-24",
            "group_id": "1", "teacher_id": "1", "room_id": "1"}, access_jar, "lesson_id", True, 204, False)
    testId("Update timetable element", "POST", "/intezmeny/update/timetable_element",
           {"intezmeny_id": f"{intezmeny_id}", "element_id": "4", "duration": "03:03:03", "day": "5", "from": "2021-11-23", "until": "2021-11-24",
            "group_id": "1", "lesson_id": "1", "room_id": "1"}, access_jar, "teacher_id", True, 204, False)
    testId("Update timetable element", "POST", "/intezmeny/update/timetable_element",
           {"intezmeny_id": f"{intezmeny_id}", "element_id": "5", "duration": "03:03:03", "day": "5", "from": "2021-11-23", "until": "2021-11-24",
            "group_id": "1", "lesson_id": "1", "teacher_id": "1"}, access_jar, "room_id", True, 204, False)
    testEndpoint("Update timetable element, until is before from", "POST", "/intezmeny/update/timetable_element", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "element_id": "1", "duration": "03:03:03", "day": "5", "from": "2021-11-23", "until": "2021-10-24",
                  "group_id": "1", "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, 400, "Bad request")
    testEndpoint("Update timetable element, until is the same day as from", "POST", "/intezmeny/update/timetable_element", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "element_id": "1", "duration": "03:03:03", "day": "5", "from": "2021-11-23", "until": "2021-11-23",
                  "group_id": "1", "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, 204, "")
    testToken("Update timetable element", "POST", "/intezmeny/update/timetable_element",
              {"intezmeny_id": f"{intezmeny_id}", "element_id": "1", "duration": "03:03:03", "day": "5", "from": "2021-11-23", "until": "2021-11-24",
               "group_id": "1", "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, wrong_access_jar)
    testEndpoint("Update timetable element, method is not POST", "PATCH", "/intezmeny/update/timetable_element", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "element_id": "1", "duration": "03:03:03", "day": "5", "from": "2021-11-23", "until": "2021-11-24",
                  "group_id": "1", "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, 405, "")
    testEndpoint("Update timetable element", "POST", "/intezmeny/update/timetable_element", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "element_id": "1", "duration": "03:03:03", "day": "5", "from": "2021-11-23", "until": "2021-11-24",
                  "group_id": "1", "lesson_id": "1", "teacher_id": "1", "room_id": "1"}, 204, "")

    testId("Update homework", "POST", "/intezmeny/update/homework",
           {"homework_id": "1", "due": "2021-11-23 03:03:03", "lesson_id": "1", "teacher_id": "1"}, access_jar, "intezmeny_id", False, 204, True)
    testId("Update homework", "POST", "/intezmeny/update/homework",
           {"intezmeny_id": f"{intezmeny_id}", "due": "2021-11-23 03:03:03", "lesson_id": "1", "teacher_id": "1"}, access_jar, "intezmeny_id", False, 204, False)
    testDateTime("Update homework", "POST", "/intezmeny/update/homework", {"intezmeny_id": f"{intezmeny_id}", "homework_id": "2", "lesson_id": "1", "teacher_id": "1"},
                 access_jar, "due", True, 204, True, True)
    testId("Update homework", "POST", "/intezmeny/update/homework",
           {"intezmeny_id": f"{intezmeny_id}", "homework_id": "3", "due": "2021-11-23 03:03:03", "teacher_id": "1"}, access_jar, "lesson_id", True, 204, False)
    testId("Update homework", "POST", "/intezmeny/update/homework",
           {"intezmeny_id": f"{intezmeny_id}", "homework_id": "4", "due": "2021-11-23 03:03:03", "lesson_id": "1"}, access_jar, "teacher_id", True, 204, False)
    testToken("Update homework", "POST", "/intezmeny/update/homework",
              {"intezmeny_id": f"{intezmeny_id}", "homework_id": "1", "due": "2021-11-23 03:03:03", "lesson_id": "1", "teacher_id": "1"}, wrong_access_jar)
    testEndpoint("Update homework, method is not POST", "PATCH", "/intezmeny/update/homework", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "homework_id": "1", "due": "2021-11-23 03:03:03", "lesson_id": "1", "teacher_id": "1"},
                 405, "")
    testEndpoint("Update homework", "POST", "/intezmeny/update/homework", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "homework_id": "1", "due": "2021-11-23 03:03:03", "lesson_id": "1", "teacher_id": "1"},
                 204, "")


def intezmenyGetEndpoints():
    global access_jar
    global wrong_access_jar
    global intezmeny_id

    testEndpoint("Get classes", "POST", "/intezmeny/get/classes", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 200, '[["1","test_class_updated"]]')
    testId("Get classes", "POST", "/intezmeny/get/classes", {}, access_jar, "intezmeny_id", False, 200, True)
    testToken("Get classes", "POST", "/intezmeny/get/classes", {"intezmeny_id": f"{intezmeny_id}"}, wrong_access_jar)
    testEndpoint("Get classes, method not POST", "PATCH", "/intezmeny/get/classes", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 405, "")

    testEndpoint("Get lessons", "POST", "/intezmeny/get/lessons", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 200, '[["1","test_lesson_updated"]]')
    testId("Get lessons", "POST", "/intezmeny/get/lessons", {}, access_jar, "intezmeny_id", False, 200, True)
    testToken("Get lessons", "POST", "/intezmeny/get/lessons", {"intezmeny_id": f"{intezmeny_id}"}, wrong_access_jar)
    testEndpoint("Get lessons, method not POST", "PATCH", "/intezmeny/get/lessons", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 405, "")

    testEndpoint("Get groups", "POST", "/intezmeny/get/groups", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 200, '[["1","test_group_updated","40","1","test_class_updated"],["2","test_group_updated_no_class_id","40",null,null],["3","test_group","30","1","test_class_updated"]]')
    testId("Get groups", "POST", "/intezmeny/get/groups", {}, access_jar, "intezmeny_id", False, 200, True)
    testToken("Get groups", "POST", "/intezmeny/get/groups", {"intezmeny_id": f"{intezmeny_id}"}, wrong_access_jar)
    testEndpoint("Get groups, method not POST", "PATCH", "/intezmeny/get/groups", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 405, "")

    testEndpoint("Get rooms", "POST", "/intezmeny/get/rooms", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 200, '[["1","test_room_updated","test_updated","40"],["2","test_room_updated_no_type",null,"40"]]')
    testId("Get rooms", "POST", "/intezmeny/get/rooms", {}, access_jar, "intezmeny_id", False, 200, True)
    testToken("Get rooms", "POST", "/intezmeny/get/rooms", {"intezmeny_id": f"{intezmeny_id}"}, wrong_access_jar)
    testEndpoint("Get rooms, method not POST", "PATCH", "/intezmeny/get/rooms", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 405, "")

    testEndpoint("Get teachers", "POST", "/intezmeny/get/teachers", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 200, f'[["1","test_teacher_updated_no_user","test_updated",null,[],[]],["2","test_teacher_updated","test_updated","{teacher_uid}",[],[]]]')
    testId("Get teachers", "POST", "/intezmeny/get/teachers", {}, access_jar, "intezmeny_id", False, 200, True)
    testToken("Get teachers", "POST", "/intezmeny/get/teachers", {"intezmeny_id": f"{intezmeny_id}"}, wrong_access_jar)
    testEndpoint("Get teachers, method not POST", "PATCH", "/intezmeny/get/teachers", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 405, "")

    testEndpoint("Get timetable", "POST", "/intezmeny/get/timetable", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 200,
                 '[["1","03:03:03","5","2021-11-23","2021-11-24","1","1","1","1"],["2","03:03:03","5","2021-11-23","2021-11-24",null,"1","1","1"],["3","03:03:03","5","2021-11-23","2021-11-24","1",null,"1","1"],["4","03:03:03","5","2021-11-23","2021-11-24","1","1",null,"1"],["5","03:03:03","5","2021-11-23","2021-11-24","1","1","1",null],["6","02:02:02","4","2020-12-24","2020-12-25","1","1","1","1"]]')
    testId("Get timetable", "POST", "/intezmeny/get/timetable", {}, access_jar, "intezmeny_id", False, 200, True)
    testToken("Get timetable", "POST", "/intezmeny/get/timetable", {"intezmeny_id": f"{intezmeny_id}"}, wrong_access_jar)
    testEndpoint("Get timetable, method not POST", "PATCH", "/intezmeny/get/timetable", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 405, "")

    response = testEndpointNoErrorHandling("POST", "/intezmeny/get/homeworks", access_jar, {"intezmeny_id": f"{intezmeny_id}"})
    handleApiError("Get homeworks", response, 200, f'[["1","{response.json()[0][1]}","2021-11-23 03:03:03","test_lesson_updated","test_teacher_updated_no_user",[[1,"test_file"],[2,"test_file"],[3,"test_file"]]],["2","{response.json()[1][1]}",null,"test_lesson_updated","test_teacher_updated_no_user",[]],["3","{response.json()[2][1]}","2021-11-23 03:03:03",null,"test_teacher_updated_no_user",[]],["4","{response.json()[3][1]}","2021-11-23 03:03:03","test_lesson_updated",null,[]],["5","{response.json()[4][1]}","2020-12-24 02:02:02","test_lesson_updated",null,[]]]')
    testId("Get homeworks", "POST", "/intezmeny/get/homeworks", {}, access_jar, "intezmeny_id", False, 200, True)
    testToken("Get homeworks", "POST", "/intezmeny/get/homeworks", {"intezmeny_id": f"{intezmeny_id}"}, wrong_access_jar)
    testEndpoint("Get homeworks, method not POST", "PATCH", "/intezmeny/get/homeworks", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}"}, 405, "")

    testEndpoint("Get attachment", "POST", "/intezmeny/get/attachment", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "attachment_id": "1"}, 200, "test_text test_text\ntest_text")
    testId("Get attachment", "POST", "/intezmeny/get/attachment", {"attachment_id": "1"}, access_jar, "intezmeny_id", False, 200, True)
    testId("Get attachment", "POST", "/intezmeny/get/attachment", {"intezmeny_id": f"{intezmeny_id}"}, access_jar, "attachment_id", False, 200, True)
    testToken("Get attachment", "POST", "/intezmeny/get/attachment", {"intezmeny_id": f"{intezmeny_id}", "attachment_id": "1"}, wrong_access_jar)
    testEndpoint("Get attachment, method not POST", "PATCH", "/intezmeny/get/attachment", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "attachment_id": "0"}, 405, "")


def intezmenyDeleteEndpoints():
    global access_jar
    global wrong_access_jar
    global intezmeny_id

    testEndpoint("Delete class", "DELETE", "/intezmeny/delete/class", access_jar, {"intezmeny_id": f"{intezmeny_id}", "class_id": "1"}, 204, '')
    testId("Delete class", "DELETE", "/intezmeny/delete/class", {"class_id": "1"}, access_jar, "intezmeny_id", False, 204, True)
    testId("Delete class", "DELETE", "/intezmeny/delete/class", {"intezmeny_id": f"{intezmeny_id}"}, access_jar, "class_id", False, 204, False)
    testToken("Delete class", "DELETE", "/intezmeny/delete/class", {"intezmeny_id": f"{intezmeny_id}", "class_id": "1"}, wrong_access_jar)
    testEndpoint("Delete class, method not DELETE", "PATCH", "/intezmeny/delete/class", access_jar, {"intezmeny_id": f"{intezmeny_id}", "class_id": "1"}, 405, "")

    testEndpoint("Delete lesson", "DELETE", "/intezmeny/delete/lesson", access_jar, {"intezmeny_id": f"{intezmeny_id}", "lesson_id": "1"}, 204, '')
    testId("Delete lesson", "DELETE", "/intezmeny/delete/lesson", {"lesson_id": "1"}, access_jar, "intezmeny_id", False, 204, True)
    testId("Delete lesson", "DELETE", "/intezmeny/delete/lesson", {"intezmeny_id": f"{intezmeny_id}"}, access_jar, "lesson_id", False, 204, False)
    testToken("Delete lesson", "DELETE", "/intezmeny/delete/lesson", {"intezmeny_id": f"{intezmeny_id}", "lesson_id": "1"}, wrong_access_jar)
    testEndpoint("Delete lesson, method not DELETE", "PATCH", "/intezmeny/delete/lesson", access_jar, {"intezmeny_id": f"{intezmeny_id}", "lesson_id": "1"}, 405, "")

    testEndpoint("Delete group", "DELETE", "/intezmeny/delete/group", access_jar, {"intezmeny_id": f"{intezmeny_id}", "group_id": "1"}, 204, '')
    testId("Delete group", "DELETE", "/intezmeny/delete/group", {"group_id": "1"}, access_jar, "intezmeny_id", False, 204, True)
    testId("Delete group", "DELETE", "/intezmeny/delete/group", {"intezmeny_id": f"{intezmeny_id}"}, access_jar, "group_id", False, 204, False)
    testToken("Delete group", "DELETE", "/intezmeny/delete/group", {"intezmeny_id": f"{intezmeny_id}", "group_id": "1"}, wrong_access_jar)
    testEndpoint("Delete group, method not DELETE", "PATCH", "/intezmeny/delete/group", access_jar, {"intezmeny_id": f"{intezmeny_id}", "group_id": "1"}, 405, "")

    testEndpoint("Delete room", "DELETE", "/intezmeny/delete/room", access_jar, {"intezmeny_id": f"{intezmeny_id}", "room_id": "1"}, 204, '')
    testId("Delete room", "DELETE", "/intezmeny/delete/room", {"room_id": "1"}, access_jar, "intezmeny_id", False, 204, True)
    testId("Delete room", "DELETE", "/intezmeny/delete/room", {"intezmeny_id": f"{intezmeny_id}"}, access_jar, "room_id", False, 204, False)
    testToken("Delete room", "DELETE", "/intezmeny/delete/room", {"intezmeny_id": f"{intezmeny_id}", "room_id": "1"}, wrong_access_jar)
    testEndpoint("Delete room, method not DELETE", "PATCH", "/intezmeny/delete/room", access_jar, {"intezmeny_id": f"{intezmeny_id}", "room_id": "1"}, 405, "")

    testEndpoint("Delete teacher", "DELETE", "/intezmeny/delete/teacher", access_jar, {"intezmeny_id": f"{intezmeny_id}", "teacher_id": "1"}, 204, '')
    testId("Delete teacher", "DELETE", "/intezmeny/delete/teacher", {"teacher_id": "1"}, access_jar, "intezmeny_id", False, 204, True)
    testId("Delete teacher", "DELETE", "/intezmeny/delete/teacher", {"intezmeny_id": f"{intezmeny_id}"}, access_jar, "teacher_id", False, 204, False)
    testToken("Delete teacher", "DELETE", "/intezmeny/delete/teacher", {"intezmeny_id": f"{intezmeny_id}", "teacher_id": "1"}, wrong_access_jar)
    testEndpoint("Delete teacher, method not DELETE", "PATCH", "/intezmeny/delete/teacher", access_jar, {"intezmeny_id": f"{intezmeny_id}", "teacher_id": "1"}, 405, "")
    
    testEndpoint("Delete timetable_element", "DELETE", "/intezmeny/delete/timetable_element", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "timetable_element_id": "1"}, 204, '')
    testId("Delete timetable_element", "DELETE", "/intezmeny/delete/timetable_element",
           {"timetable_element_id": "1"}, access_jar, "intezmeny_id", False, 204, True)
    testId("Delete timetable_element", "DELETE", "/intezmeny/delete/timetable_element",
           {"intezmeny_id": f"{intezmeny_id}"}, access_jar, "timetable_element_id", False, 204, False)
    testToken("Delete timetable_element", "DELETE", "/intezmeny/delete/timetable_element",
              {"intezmeny_id": f"{intezmeny_id}", "timetable_element_id": "1"}, wrong_access_jar)
    testEndpoint("Delete timetable_element, method not DELETE", "PATCH", "/intezmeny/delete/timetable_element", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "timetable_element_id": "1"}, 405, "")

    testEndpoint("Delete attachment", "DELETE", "/intezmeny/delete/attachment", access_jar, {"intezmeny_id": f"{intezmeny_id}", "attachment_id": "1"}, 204, "")
    testId("Delete attachment", "DELETE", "/intezmeny/delete/attachment", {"attachment_id": "1"}, access_jar, "intezmeny_id", False, 204, True)
    testId("Delete attachment", "DELETE", "/intezmeny/delete/attachment", {"intezmeny_id": f"{intezmeny_id}"}, access_jar, "attachment_id", False, 204, False)
    testToken("Delete attachment", "DELETE", "/intezmeny/delete/attachment", {"intezmeny_id": f"{intezmeny_id}", "attachment_id": "1"}, wrong_access_jar)
    testEndpoint("Delete attachment, method not DELETE", "PATCH", "/intezmeny/delete/attachment", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "attachment_id": "1"}, 405, "")

    testEndpoint("Delete homework", "DELETE", "/intezmeny/delete/homework", access_jar, {"intezmeny_id": f"{intezmeny_id}", "homework_id": "1"}, 204, '')
    testId("Delete homework", "DELETE", "/intezmeny/delete/homework", {"homework_id": "1"}, access_jar, "intezmeny_id", False, 204, True)
    testId("Delete homework", "DELETE", "/intezmeny/delete/homework", {"intezmeny_id": f"{intezmeny_id}"}, access_jar, "homework_id", False, 204, False)
    testToken("Delete homework", "DELETE", "/intezmeny/delete/homework", {"intezmeny_id": f"{intezmeny_id}", "homework_id": "1"}, wrong_access_jar)
    testEndpoint("Delete homework, method not DELETE", "PATCH", "/intezmeny/delete/homework", access_jar,
                 {"intezmeny_id": f"{intezmeny_id}", "homework_id": "1"}, 405, "")


def deleteIntezmeny():            
    global access_jar
    global wrong_access_jar
    global intezmeny_id

    testEndpoint("Delete intezmeny", "DELETE", "/delete_intezmeny", access_jar, {"intezmeny_id": f"{intezmeny_id}"}, 204, "")
    testId("Delete intezmeny", "DELETE", "/delete_intezmeny", {}, access_jar, "intezmeny_id", False, 204, True)
    testToken("Delete intezmeny", "DELETE", "/delete_intezmeny", {"intezmeny_id": f"{intezmeny_id}"}, wrong_access_jar)
    testEndpoint("Delete intezmeny, method is not DELETE", "PATCH", "/delete_intezmeny", access_jar, {"intezmeny_id": f"{intezmeny_id}"}, 405, "")


def deleteUser():
    global access_jar
    global wrong_access_jar

    testToken("Delete user", "DELETE", "/user/delete", {"pass": "tester_pass+"}, wrong_access_jar)
    testEndpoint("Delete user, method is not DELETE", "PATCH", "/user/delete", access_jar, {"pass": "tester_pass+"}, 405, "")
    testEndpoint("Delete user", "DELETE", "/user/delete", access_jar, {"pass": "tester_pass+"}, 204, "")
    testEndpoint("Delete user, user does not exist", "DELETE", "/user/delete", access_jar, {"pass": "tester_pass+"}, 403, "Unauthorised")


def cleanup():
    global teacher_access_jar

    no_phone_refresh_jar = dict()
    no_phone_access_jar = dict()

    no_phone_refresh_jar = testEndpoint("Get refresh token for no phone user", "POST", "/token/get_refresh_token", "",
                                        {"email": "tester_no_phone@test.com", "pass": "tester_pass+"}, 200, "").cookies
    no_phone_access_jar = testEndpoint("Get access token for no phone user", "GET", "/token/get_access_token", no_phone_refresh_jar, {}, 200, "").cookies
    testEndpoint("Delete no phone number user", "DELETE", "/user/delete", no_phone_access_jar, {"pass": "tester_pass+"}, 204, "")
    testEndpoint("Delete teacher user", "DELETE", "/user/delete", teacher_access_jar, {"pass": "tester_pass+"}, 204, "")


main()
    
