/**
 * WebAuthn / Passkeys client helper.
 * Base64url encode/decode and credential create/get for registration and authentication.
 */
(function () {
  'use strict';

  function base64urlDecode(str) {
    var padding = (4 - (str.length % 4)) % 4;
    var base64 = str.replace(/-/g, '+').replace(/_/g, '/') + '==='.slice(0, padding);
    var binary = atob(base64);
    var bytes = new Uint8Array(binary.length);
    for (var i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
    return bytes.buffer;
  }

  function base64urlEncode(buffer) {
    var bytes = new Uint8Array(buffer);
    var binary = '';
    for (var i = 0; i < bytes.length; i++) binary += String.fromCharCode(bytes[i]);
    var base64 = btoa(binary);
    return base64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  }

  function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) return meta.getAttribute('content');
    var input = document.querySelector('input[name="_token"]');
    return input ? input.value : '';
  }

  function getBasePath() {
    var meta = document.querySelector('meta[name="app-base-path"]');
    return (meta && meta.getAttribute('content')) ? meta.getAttribute('content') : '';
  }

  function prepareCreationOptions(options) {
    if (!options || !options.publicKey) return options;
    var pk = options.publicKey;
    if (pk.challenge && typeof pk.challenge === 'string') {
      pk.challenge = base64urlDecode(pk.challenge);
    }
    if (pk.user && pk.user.id && typeof pk.user.id === 'string') {
      pk.user.id = base64urlDecode(pk.user.id);
    }
    if (Array.isArray(pk.excludeCredentials)) {
      pk.excludeCredentials = pk.excludeCredentials.map(function (c) {
        var cred = { type: c.type || 'public-key', id: c.id };
        if (typeof c.id === 'string') cred.id = base64urlDecode(c.id);
        if (c.transports) cred.transports = c.transports;
        return cred;
      });
    }
    return options;
  }

  function prepareRequestOptions(options) {
    if (!options || !options.publicKey) return options;
    var pk = options.publicKey;
    if (pk.challenge && typeof pk.challenge === 'string') {
      pk.challenge = base64urlDecode(pk.challenge);
    }
    if (Array.isArray(pk.allowCredentials) && pk.allowCredentials.length > 0) {
      pk.allowCredentials = pk.allowCredentials.map(function (c) {
        var cred = { type: c.type || 'public-key', id: c.id };
        if (typeof c.id === 'string') cred.id = base64urlDecode(c.id);
        if (c.transports) cred.transports = c.transports;
        return cred;
      });
    }
    return options;
  }

  function fetchJson(url, opts) {
    opts = opts || {};
    var basePath = getBasePath();
    var fullUrl = (basePath ? basePath : '') + url;
    var headers = opts.headers || {};
    headers['Content-Type'] = headers['Content-Type'] || 'application/json';
    var token = getCsrfToken();
    if (token) headers['X-CSRF-Token'] = token;
    return fetch(fullUrl, {
      method: opts.method || 'GET',
      headers: headers,
      body: opts.body,
      credentials: 'same-origin'
    }).then(function (r) {
      return r.text().then(function (text) {
        var data;
        try {
          data = text ? JSON.parse(text) : {};
        } catch (e) {
          if (text && text.indexOf('<') !== -1) {
            throw new Error('Server returned HTML instead of JSON. You may have been logged out, or the app is in a subdirectory and the base path may be wrong. Check the browser console for the request URL.');
          }
          throw new Error(r.statusText || 'Invalid response');
        }
        if (!r.ok) throw new Error((data && data.error) || r.statusText);
        return data;
      });
    });
  }

  var WebAuthnHelper = {
    supported: typeof window.PublicKeyCredential !== 'undefined',

    register: function (nickname) {
      return fetchJson('/passkeys/register/start.php', { method: 'POST', body: '{}' })
        .then(function (options) {
          options = prepareCreationOptions(options);
          return navigator.credentials.create(options);
        })
        .then(function (cred) {
          if (!cred) throw new Error('No credential returned');
          var response = cred.response;
          var payload = {
            clientDataJSON: base64urlEncode(response.clientDataJSON),
            attestationObject: base64urlEncode(response.attestationObject),
            credentialId: base64urlEncode(cred.rawId)
          };
          if (nickname) payload.nickname = nickname;
          return fetchJson('/passkeys/register/finish.php', {
            method: 'POST',
            body: JSON.stringify(payload)
          });
        });
    },

    authenticate: function (email) {
      var body = email ? JSON.stringify({ email: email }) : '{}';
      return fetchJson('/passkeys/auth/start.php', { method: 'POST', body: body })
        .then(function (options) {
          options = prepareRequestOptions(options);
          return navigator.credentials.get(options);
        })
        .then(function (cred) {
          if (!cred) throw new Error('No credential returned');
          var response = cred.response;
          return fetchJson('/passkeys/auth/finish.php', {
            method: 'POST',
            body: JSON.stringify({
              clientDataJSON: base64urlEncode(response.clientDataJSON),
              authenticatorData: base64urlEncode(response.authenticatorData),
              signature: base64urlEncode(response.signature),
              credentialId: base64urlEncode(cred.rawId)
            })
          });
        });
    },

    listCredentials: function () {
      return fetchJson('/passkeys/list.php', { method: 'GET' });
    },

    renameCredential: function (id, nickname) {
      return fetchJson('/passkeys/rename.php', {
        method: 'POST',
        body: JSON.stringify({ id: id, nickname: nickname })
      });
    },

    removeCredential: function (id) {
      return fetchJson('/passkeys/remove.php', {
        method: 'POST',
        body: JSON.stringify({ id: id })
      });
    },

    initSecurityPage: function (listEl, addBtn, hintEl) {
      if (!this.supported) {
        if (hintEl) hintEl.style.display = 'block';
        if (addBtn) addBtn.disabled = true;
        return;
      }
      function renderList() {
        WebAuthnHelper.listCredentials().then(function (data) {
          listEl.innerHTML = '';
          if (!data.credentials || data.credentials.length === 0) {
            listEl.innerHTML = '<p class="muted">No passkeys yet. Add one below.</p>';
            return;
          }
          data.credentials.forEach(function (c) {
            var row = document.createElement('div');
            row.className = 'passkey-item';
            row.dataset.id = c.id;
            var left = document.createElement('div');
            var name = document.createElement('span');
            name.textContent = c.nickname || ('Passkey ' + (c.credentialIdMask || c.id));
            var credId = document.createElement('span');
            credId.className = 'cred-id';
            credId.textContent = ' ' + (c.credentialIdMask || '');
            var meta = document.createElement('div');
            meta.className = 'muted';
            meta.textContent = (c.lastUsedAt ? 'Last used ' + c.lastUsedAt.slice(0, 10) : 'Created ' + (c.createdAt || '').slice(0, 10));
            left.appendChild(name);
            left.appendChild(credId);
            left.appendChild(meta);
            var actions = document.createElement('div');
            actions.className = 'actions';
            var renameBtn = document.createElement('button');
            renameBtn.type = 'button';
            renameBtn.textContent = 'Rename';
            renameBtn.addEventListener('click', function () {
              var n = prompt('Nickname for this passkey', c.nickname || '');
              if (n === null) return;
              WebAuthnHelper.renameCredential(c.id, n).then(renderList).catch(function (e) { alert(e.message); });
            });
            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'danger';
            removeBtn.textContent = 'Remove';
            removeBtn.addEventListener('click', function () {
              if (!confirm('Remove this passkey? You will not be able to sign in with it.')) return;
              WebAuthnHelper.removeCredential(c.id).then(renderList).catch(function (e) { alert(e.message); });
            });
            actions.appendChild(renameBtn);
            actions.appendChild(removeBtn);
            row.appendChild(left);
            row.appendChild(actions);
            listEl.appendChild(row);
          });
        }).catch(function (e) {
          listEl.innerHTML = '<p class="alert alert-error">' + (e.message || 'Failed to load') + '</p>';
        });
      }
      renderList();
      if (addBtn) {
        addBtn.addEventListener('click', function () {
          addBtn.disabled = true;
          WebAuthnHelper.register().then(function () {
            renderList();
            addBtn.disabled = false;
          }).catch(function (e) {
            alert(e.message || 'Registration failed');
            addBtn.disabled = false;
          });
        });
      }
    },

    initLoginPage: function (passkeyBtn, emailInput) {
      if (!this.supported) {
        if (passkeyBtn) passkeyBtn.style.display = 'none';
        return;
      }
      if (!passkeyBtn) return;
      passkeyBtn.addEventListener('click', function () {
        var email = emailInput && emailInput.value ? emailInput.value.trim() : '';
        passkeyBtn.disabled = true;
        WebAuthnHelper.authenticate(email || undefined).then(function (data) {
          if (data.redirect) window.location.href = data.redirect;
          else window.location.href = '/admin/';
        }).catch(function (e) {
          alert(e.message || 'Sign in with passkey failed');
          passkeyBtn.disabled = false;
        });
      });
    }
  };

  window.WebAuthnHelper = WebAuthnHelper;
})();
