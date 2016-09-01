# Butter AMQP

_This is really work in progress AMQP library written purely in PHP, supporting only AMQP 0.9.1 (at least for the moment)._
 
This library provides functional level interfaces for interacting with AMQP server.

More documentation and complete support for AMQP 0.9.1 coming soon, but feel free to leave any suggestion or give feedback.

## To do

### Features

- [ ] Error response handling, proper Exception types
- [ ] Handle connection errors (connecting, reading, writing)
- [ ] SSL support
- [x] Split content frames into multiple pieces if frame-max exceeded
- [x] Collect all data pieces when getting basic.delivery
- [x] Implement non-blocking reading and reading timeout
- [x] Implement heartbeat
- [ ] Send Client Capabilities

### Frames

- [x] Basic ACK
- [x] Basic Cancel
- [x] Basic Consume
- [x] Basic Deliver
- [ ] Basic Get
- [x] Basic NACK
- [x] Basic Publish
- [x] Basic QOS
- [ ] Basic Recover
- [x] Basic Reject
- [ ] Basic Return
- [x] Channel Close
- [x] Channel Flow
- [x] Channel Open
- [ ] Confirm Select
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
- [ ] Tx Commit
- [ ] Tx Rollback
- [ ] Tx Select
