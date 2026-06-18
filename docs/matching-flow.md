# Matching Flow

## Match

1. User clicks Match
2. Add user to waiting queue
3. Search waiting user
4. Create room
5. Save match state
6. Send matched event

---

## Leave

1. Remove match state
2. Remove room state
3. Notify partner

---

## Rematch

1. Leave current room
2. Clean states
3. Add waiting queue
4. Search new partner

---

## Disconnect

1. Remove waiting queue
2. Remove room state
3. Remove connection state
4. Notify partner