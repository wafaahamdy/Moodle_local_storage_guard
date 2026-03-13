# storage_guard #

Storage Guard is a Moodle plugin that manages course storage quotas and enforces storage limits to prevent excessive file storage usage on your Moodle instance.

## Features

- **Course-level quota management**: Set storage limits per course or use site-wide defaults
- **Configurable Recycle Bin Logic**: toggle whether items in the Recycle Bin count toward the course quota.
- **Automatic quota enforcement**: Locks courses to 1MB upload limit when quota is exceeded
- **Warning notifications**: Alerts teachers when storage usage reaches 80% of quota
- **Restriction notifications**: Alerts when storage is restricted due to quota exceeded
- **Custom field integration**: Course-level quota overrides via custom fields

## Important: Understanding File Storage in Moodle

When you **copy a course** in Moodle, the system does NOT physically duplicate all files on disk. Instead, it creates **database references** to the same files. This is by design for efficiency.
When you delete content from copied course only the database records are removed ( as physical files are still used by other course)
- Storage Guard correctly reports the usage based on database records, not physical files

### Recycle bin and course size calculations
- Include Recycle Bin(off) default:
Items in recycle bin are not counted in course qouta.

- Include Recycle Bin(on):
Items recycle bin are counted in course qouta and must be deleted from recycle bin or deactivate recycle bin.

Note:in Moodle, the deleted file doesn't move the recyle bin until recycle bin cleanup scheduled task run.

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
2026 Wafaa Hamdy <eng.wafaa.hamdy@gmail.com>
under the terms of the GNU General Public License. see <https://www.gnu.org/licenses/>.
