# storage_guard #

Storage Guard is a Moodle plugin that manages course storage quotas and enforces storage limits to prevent excessive file storage usage on your Moodle instance.

## Features

- **Course-level quota management**: Set storage limits per course or use site-wide defaults
- **Automatic quota enforcement**: Locks courses to 1MB upload limit when quota is exceeded
- **Warning notifications**: Alerts teachers when storage usage reaches 80% of quota
- **Restriction notifications**: Alerts when storage is restricted due to quota exceeded
- **Orphaned file cleanup**: Automatically removes unreferenced files from storage
- **Custom field integration**: Course-level quota overrides via custom fields

## Important: Understanding File Storage in Moodle

When you **copy a course** in Moodle, the system does NOT physically duplicate all files on disk. Instead, it creates **database references** to the same files. This is by design for efficiency.

### Why storage size remains the same after deleting course content:

1. When copying Course 1 → Course 2, files are NOT duplicated on disk
2. Moodle creates new database entries (`{files}` table) pointing to the same physical files
3. When you delete content from Course 2, only the database records are removed
4. The physical files remain on disk (they may still be referenced by Course 1 or are orphaned)
5. Storage Guard correctly reports the usage based on database records, not physical files

### Solution: Clean up orphaned files

This plugin includes an automatic scheduled task to clean up orphaned files. Alternatively, you can manually run:

```bash
php admin/cli/maintenance.php --purgefiles
```

This safely removes files with no database references while preserving files still in use.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/local/storage_guard

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2026 Wafaa Mansour <eng.wafaa.hamdy@gmail.com>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
