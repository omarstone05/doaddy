# SSH Setup for Production Server

## Server Information
- **IP Address**: 72.61.105.187
- **SSH Key**: `~/.ssh/addy_production`
- **SSH Alias**: `addy-production`

## ✅ Completed Steps

1. ✅ SSH key pair generated
2. ✅ SSH config configured

## 📋 Your Public Key

```
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIJBFlApsGiXX+Nu12tfOu5+hWVYHvIcS4bPAOfEATNTz addy-production-20251110
```

## 🔐 Next Steps: Copy Key to Server

### Option 1: Using ssh-copy-id (Recommended)

```bash
ssh-copy-id -i ~/.ssh/addy_production.pub root@72.61.105.187
```

You'll be prompted for the server password. After entering it, the key will be automatically added.

### Option 2: Manual Copy

If `ssh-copy-id` is not available, you can manually copy the key:

```bash
# Copy the public key content
cat ~/.ssh/addy_production.pub | ssh root@72.61.105.187 "mkdir -p ~/.ssh && chmod 700 ~/.ssh && cat >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys"
```

### Option 3: Manual Setup (if you have server access)

1. SSH into the server with password:
   ```bash
   ssh root@72.61.105.187
   ```

2. On the server, run:
   ```bash
   mkdir -p ~/.ssh
   chmod 700 ~/.ssh
   nano ~/.ssh/authorized_keys
   ```

3. Paste your public key (the one shown above) into the file

4. Set proper permissions:
   ```bash
   chmod 600 ~/.ssh/authorized_keys
   ```

## ✅ Test Connection

After copying the key, test the connection:

```bash
ssh addy-production
```

Or:

```bash
ssh -i ~/.ssh/addy_production root@72.61.105.187
```

If successful, you should be able to connect without entering a password.

## 🔧 Troubleshooting

### Permission Denied
- Ensure the server's `~/.ssh` directory has 700 permissions
- Ensure `authorized_keys` has 600 permissions
- Check that the public key was added correctly

### Connection Refused
- Verify the server IP is correct: 72.61.105.187
- Check if SSH service is running on the server
- Verify firewall allows SSH (port 22)

### Wrong User
If you need to use a different user (not `root`), edit `~/.ssh/config`:

```
Host addy-production
    HostName 72.61.105.187
    User your-username
    IdentityFile ~/.ssh/addy_production
```

## 📝 Notes

- The SSH key is stored at: `~/.ssh/addy_production`
- The public key is at: `~/.ssh/addy_production.pub`
- Keep your private key (`addy_production`) secure and never share it
- The SSH config alias `addy-production` allows you to connect with just: `ssh addy-production`

