# WebSocket Protocol

## Client → Server

### match_start

{
  "type": "match_start"
}

### match_cancel

{
  "type": "match_cancel"
}

### chat_message

{
  "type": "chat_message",
  "message": "hello"
}

### chat_leave

{
  "type": "chat_leave"
}

### rematch

{
  "type": "rematch"
}

---

## Server → Client

### waiting

{
  "type": "waiting"
}

### matched

{
  "type": "matched"
}

### message

{
  "type": "message"
}

### partner_left

{
  "type": "partner_left"
}

### error

{
  "type": "error"
}