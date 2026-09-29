// Mirrors the basic rules enforced by the backend (App\Validation\PasswordRules).
// The server still decides: it also rejects common passwords and passwords
// containing the username.
export const PASSWORD_HINT = 'At least 8 characters, with a letter and a number.';

export function checkPassword(password) {
    if (password.length < 8) return 'Password must be at least 8 characters.';
    if (!/[A-Za-z]/.test(password) || !/\d/.test(password)) return 'Password must contain at least one letter and one number.';
    return '';
}
