# Butter AMQP

_This is really work in progress AMQP library written purely in PHP, supporting only AMQP 0.9.1 (at least for the moment)._
 
This library provides functional level interfaces for interacting with AMQP server.

More documentation and complete support for AMQP 0.9.1 coming soon, but feel free to leave any suggestion or give feedback.

## To do

### Features

- [x] Error response handling, proper Exception types
- [x] Handle connection errors (connecting, reading, writing)
- [x] SSL support
- [x] Split content frames into multiple pieces if frame-max exceeded
- [x] Collect all data pieces when getting basic.delivery
- [x] Implement non-blocking reading and reading timeout
- [x] Implement heartbeat
- [x] Send Client Capabilities
- [ ] Decimal type support
- [ ] Verify and fix long long and unsigned long long type

### Frames

- [x] Basic ACK
- [x] Basic Cancel
- [x] Basic Consume
- [x] Basic Deliver
- [x] Basic Get
- [x] Basic NACK
- [x] Basic Publish
- [x] Basic QOS
- [x] Basic Recover
- [x] Basic Reject
- [x] Basic Return
- [x] Channel Close
- [x] Channel Flow
- [x] Channel Open
- [x] Confirm Select
- [x] Connection Blocked
- [x] Connection Close
- [x] Connection Open
- [ ] Connection Secure
- [x] Connection Start
- [x] Connection Tune
- [x] Connection Unblocked
- [x] Exchange Bind
- [x] Exchange Declare
- [x] Exchange Delete
- [x] Exchange Unbind
- [x] Queue Bind
- [x] Queue Declare
- [x] Queue Delete
- [x] Queue Purge
- [x] Queue Unbind
- [x] Tx Commit
- [x] Tx Rollback
- [x] Tx Select
