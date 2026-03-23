
export function validateString(id, err_id, max_len, min_len, name) {
  const val = document.getElementById(id).value;
  if (val.length === 0) {
    document.getElementById(err_id).innerHTML = "Úres " + name + " nem megengedett<br>";
    return false;
  } else if (val.length > max_len) {
    document.getElementById(err_id).innerHTML = "A " + name + " maximum hossza " + max_len + "<br>";
    return false;
  } else if (val.length < min_len) {
    document.getElementById(err_id).innerHTML = "A " + name + " minimum hossza " + min_len + "<br>";
    return false;
  } else {
    document.getElementById(err_id).innerHTML = "";
    return val;
  }
}

export function validateEmail(id, err_id) {
  const val = document.getElementById(id).value;
  if (val.length === 0) {
    document.getElementById(err_id).innerHTML = "Úres email nem megengedett<br>";
    return false;
  } else if (val.length > 254) {
    document.getElementById(err_id).innerHTML = "A email maximum hossza 254<br>";
    return false;
  } else if (val.match(/^[^@]+[@]+[^@]+$/) === null) {
    document.getElementById(err_id).innerHTML = "Nem valid email<br>";
    return false;
  } else {
    document.getElementById(err_id).innerHTML = "";
    return val;
  }
}

export function validateTelefon(id, err_id) {
  const val = document.getElementById(id).value;
  if (val.length === 0) {
    document.getElementById(err_id).innerHTML = "";
    return "";
  } else if (val.length > 15) {
    document.getElementById(err_id).innerHTML = "A telefonszám maximum hossza 15<br>";
    return false;
  } else if (val.match(/^\d+$/) === null) {
    document.getElementById(err_id).innerHTML = "Nem valid telefonszám<br>";
    return false;
  } else {
    document.getElementById(err_id).innerHTML = "";
    return val;
  }
}
