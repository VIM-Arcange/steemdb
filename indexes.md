# Comment Indexes:
db.comment.createIndex({parent_permlink: 1})
db.comment.createIndex({scanned: 1, created: 1});
db.comment.createIndex({depth: 1, created: 1});
db.comment.createIndex({author: 1, depth: 1, created: 1});

# Block Indexes:
db.block.createIndex({witness: 1, _ts: 1})

# Voter Indexes:
db.vote.createIndex({voter: 1, author: 1, _ts: 1});
db.vote.createIndex({voter: 1, _ts: 1});

# Account Indexes:
db.account.createIndex({name: 1});
db.account.createIndex({created: 1});
db.account.createIndex({vesting_shares: 1});
db.account.createIndex({witness_votes: 1});

# Account History Indexes:
db.account_history.createIndex({date: 1, name: 1});

