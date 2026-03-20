
## Folder structure

```text
azure-horizon-professional-baseline/
├── app/
│   ├── config/
│   │   └── database.php        # database credentials/config only
│   └── includes/
│       ├── db.php              # PDO connection setup
│       ├── header.php          # shared header/nav
│       └── footer.php          # shared footer/scripts
├── database/
│   └── schema.sql              # SQL schema
├── public/
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css
│   │   └── js/
│   │       └── main.js
│   ├── index.php
│   ├── rooms.php
│   ├── about.php
│   ├── register.php
│   ├── login.php
│   ├── booking.php
│   ├── profile.php
│   └── logout.php
└── .vscode/
    └── launch.json
```


### Terminal
```bash
php -S localhost:8000 -t public
```

Then open:
```text
http://localhost:8000/index.php
```


Requirements: (For local machine)

- SQL workbench
- MySQL 8.0.0 and up 