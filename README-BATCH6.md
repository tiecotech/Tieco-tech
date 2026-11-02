# Batch 6: dashboard content tools
Messages inbox, users and roles, activity log, services/pages/team editors, contact details.
Roles: editor = content + view messages; manager = requests + manage messages; admin = everything incl. users and activity.
Safety: nobody can demote/deactivate the last active admin or their own admin access; passwords min 12 chars, never logged.
Files replaced: routes/admin.php, app/Http/Middleware/AdminArea.php, resources/js/components/admin/admin-layout.tsx.
