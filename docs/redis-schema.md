# Redis Schema

## Waiting Queue

random_chat:waiting_users

---

## User

random_chat:user:{userId}:connection

random_chat:user:{userId}:room

---

## Room

random_chat:room:{roomId}:users

---

## Matching

random_chat:user:{userId}:partner

---

## Notes

- One user can have only one room.
- One user can have only one partner.
- Room cleanup is mandatory.
- Disconnect cleanup is mandatory.