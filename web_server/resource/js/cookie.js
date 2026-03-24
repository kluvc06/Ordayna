
export const url = window.location.protocol + "//" + window.location.host + "/";

export function getCookie(cookieName) {
  const cookies = document.cookie.split('; ');
  for (const cookie of cookies) {
    const [name, value] = cookie.split('=');
    if (name === cookieName) {
      return decodeURIComponent(value);
    }
  }
  return null;
}

async function refresh() {
  const response = await fetch(url + "token/refresh_refresh_token", { method: "GET" });
  if (response.ok !== true) {
    location.replace('login.html');
  }
}

await refresh();

async function getAccessToken() {
  const response = await fetch(url + "token/get_access_token", { method: "GET" });
  if (response.ok !== true) {
    location.replace('login.html');
  }
  await response.text();
}

// Called every minute
setInterval(getAccessToken, 1000 * 60);

await getAccessToken();
