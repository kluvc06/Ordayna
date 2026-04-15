
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

export function validateNumber(id, err_id, max, min, name) {
  let val = document.getElementById(id).value;
  if (val.length === 0) {
    document.getElementById(err_id).innerHTML = "Úres " + name + " nem megengedett<br>";
    return false;
  } else if (val.match(/^\d+$/) === null) {
    document.getElementById(err_id).innerHTML = "A " + name + " nem egész szám<br>";
    return false;
  }
  val = parseInt(val);
  if (val > max) {
    document.getElementById(err_id).innerHTML = "A " + name + " maximum értéke " + max_len + "<br>";
    return false;
  } else if (val < min) {
    document.getElementById(err_id).innerHTML = "A " + name + " minimum értéke " + min_len + "<br>";
    return false;
  } else {
    document.getElementById(err_id).innerHTML = "";
    return val;
  }
}

export function validateDateTime(id, err_id, name) {
  let datetime = document.getElementById(id).value;
  // Check datetime format
  if (/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/gm.test(datetime) === false) {
    document.getElementById(err_id).innerHTML = "A " + name + " nem felel meg a formátumnak<br>";
    return false;
  }
  // Check if datetime is valid
  if (isNaN(new Date(datetime)) === true) {
    document.getElementById(err_id).innerHTML = "A " + name + " nem érvényes<br>";
    return false;
  }
  return datetime;
}

export function validateDate(id, err_id, name) {
  let date = document.getElementById(id).value;
  // Check date format
  if (/^(\d{4})-(\d{2})-(\d{2})$/gm.test(date) === false) {
    document.getElementById(err_id).innerHTML = "A " + name + " nem felel meg a formátumnak<br>";
    return false;
  }
  // Check if date is valid
  if (isNaN(new Date(date)) === true) {
    document.getElementById(err_id).innerHTML = "A " + name + " nem érvényes dátum<br>";
    return false;
  }
  return date;
}

export function validateTime(id, err_id, name) {
  let datetime = document.getElementById(id).value;
  // Check time format
  if (/^(\d{2}):(\d{2}):(\d{2})$/gm.test(datetime) === false) {
    document.getElementById(err_id).innerHTML = "A " + name + " nem felel meg a formátumnak<br>";
    return false;
  }
  // Check if time is valid
  if (isNaN(new Date("2000-05-05 " + datetime)) === true) {
    document.getElementById(err_id).innerHTML = "A " + name + " nem érvényes idő<br>";
    return false;
  }
  return datetime;
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
