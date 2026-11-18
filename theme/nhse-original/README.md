## NHSE TEL Moodle theme (Boost extension)

### Requirements

1. Node 19.4
2. Moodle 4.1 with Boost theme

### Installation

1. Clone that repository into your Moodle `theme/nhse` directory.

```
git clone git@github.com:TechnologyEnhancedLearning/moodle-nhse-learninghub
```

2. Before activating theme in Moodle theme selector the dependencies need loading.

```
npm install
```

Tested with NPM v9.2.0 and Node v19.4.0, you can check both versions with:

```
npm -v
node -v
```

3. Proceed with Moodle installer instructions when new theme is detected

4. If you need to change any dependencies of the SCSS files please remember to clear Moodle caches in Moodle admin panel `/admin/purgecaches.php`. Moodle SCSS compiler will do the rest for you.

### Creating a new GitHub Release

1. Update version.php

2. Update composer.json

3. Merge from develop to main

4. `git checkout main && git pull`

5. `git tag YYYYMMDDXX && git push --tags` (Moodle format where YYYYMMDD is the current date and XX is the release number, e.g. 2023042301 for the first release on 23rd April 2023)

6. Allow GitHub Actions to complete.
