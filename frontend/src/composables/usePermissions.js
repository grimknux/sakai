// composables/usePermissions.js

export function usePermissions() {
    const getPerms = () => {
        try {
            return JSON.parse(localStorage.getItem('permissions') || '[]');
        } catch {
            return [];
        }
    };

    const perms = getPerms();

    const can = (perm) => {
        return perms.includes(perm) || JSON.parse(localStorage.getItem('user') || '{}')?.is_superadmin == 1;
    };

    return {
        can
    };
}
