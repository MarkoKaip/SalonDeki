# Salon Deki — deploy na InfinityFree

## 1. Napravi nalog i sajt

1. Idi na [https://app.infinityfree.net/register](https://app.infinityfree.net/register)
2. Kreiraj hosting nalog (npr. `salondeki`)
3. Dodaj domen — besplatno: `tvoj-sajt.infinityfreeapp.com`

## 2. MySQL baza

1. U **Control Panel** → **MySQL Databases**
2. Klikni **Create Database** (npr. ime `salon`)
3. Zapiši podatke:
   - **MySQL Hostname** (npr. `sql201.infinityfree.com`)
   - **MySQL Username** (npr. `if0_12345678`)
   - **MySQL Password** (lozinka hosting naloga)
   - **MySQL Database** (npr. `if0_12345678_salon`)

## 3. Upload fajlova

**Opcija A — ZIP (brže):**

1. Lokalno napravi ZIP celog foldera `frizerski_salon`
2. Control Panel → **File Manager** → otvori `htdocs`
3. Obriši default fajlove u `htdocs`
4. Upload ZIP → **Extract**
5. Ako je ZIP imao folder unutra, pomeri sve iz `frizerski_salon/` direktno u `htdocs/`

**Opcija B — FileZilla (FTP):**

1. U panelu otvori **FTP Details**
2. Poveži se FileZilla klijentom
3. Uploaduj sve fajlove u `/htdocs`

## 4. Podesi bazu na serveru

1. U File Manageru otvori `config/database.local.php.example`
2. **Copy** → preimenuj kopiju u `database.local.php`
3. Unesi InfinityFree MySQL podatke iz koraka 2
4. Sačuvaj fajl

## 5. Instaliraj bazu

1. Otvori u browseru: `https://tvoj-sajt.infinityfreeapp.com/install.php`
2. Ako piše da je instalacija uspešna — gotovo
3. **Obriši `install.php`** sa servera (bezbednost)

## 6. Test

| Stranica | URL |
|----------|-----|
| Početna | `https://tvoj-sajt.infinityfreeapp.com/` |
| Zakazivanje | `https://tvoj-sajt.infinityfreeapp.com/booking.php` |
| Admin | `https://tvoj-sajt.infinityfreeapp.com/admin/login.php` |
| PIN | `2109` |

## Važno

- Na InfinityFree **ne koristi `localhost`** — uvek MySQL hostname iz panela
- `database.local.php` drži lozinku — ne deli ga javno
- Posle deploy-a obriši `install.php`

## Lokalno vs online

| | Lokalno (XAMPP) | Online (InfinityFree) |
|--|-----------------|----------------------|
| Config | `config/database.php` (default) | `config/database.local.php` |
| Host | localhost | sqlXXX.infinityfree.com |
