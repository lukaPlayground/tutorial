* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  font-family: -apple-system, 'Segoe UI', sans-serif;
  background: #0a0e17;
  color: #e2e8f0;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}
.container { width: 100%; max-width: 400px; padding: 20px; }
.card {
  background: #1a1d2e;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 16px;
  padding: 36px 32px;
}
.card-title {
  font-size: 24px;
  font-weight: 700;
  color: #fff;
  margin-bottom: 28px;
  text-align: center;
}
.form-group { margin-bottom: 18px; }
.form-group label {
  display: block;
  font-size: 13px;
  color: rgba(255,255,255,0.5);
  margin-bottom: 6px;
}
.hint { font-size: 11px; color: rgba(255,255,255,0.3); }
.form-group input {
  width: 100%;
  padding: 10px 14px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 8px;
  color: #e2e8f0;
  font-size: 14px;
  outline: none;
  transition: border-color .2s;
}
.form-group input:focus { border-color: #6366f1; }
.btn {
  width: 100%;
  padding: 12px;
  border: none;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  margin-top: 8px;
  transition: opacity .2s;
}
.btn:hover { opacity: .85; }
.btn-primary { background: #6366f1; color: #fff; }
.alert { border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px; }
.alert p { margin: 2px 0; }
.alert-error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #f87171; }
.card-footer { text-align: center; margin-top: 20px; font-size: 13px; color: rgba(255,255,255,0.4); }
.card-footer a { color: #818cf8; text-decoration: none; }
.card-footer a:hover { text-decoration: underline; }
