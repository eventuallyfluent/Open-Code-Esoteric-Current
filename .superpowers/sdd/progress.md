# SDD Progress Ledger

## Task 1: Worker Source Domain Filtering
- **Status:** DONE
- **Commits:** a3bc5fd (impl), 07ef1e7 (fix: add encyclopedia.com, topic param)
- **Review notes:** Added encyclopedia.com, topic param. All clear.

## Task 2: Flags Database Table
- **Status:** DONE
- **Commits:** 978d92f (impl), a77d4ba (fix: reviewed bool, dbDelta, 1.2.0 placeholder)
- **Review notes:** Fixed reviewed→reviewed_at column, added created_at index, switched to dbDelta, removed extra columns, added 1.2.0 placeholder.

## Task 3: Flag REST Endpoint
- **Status:** DONE
- **Commits:** fa80d2f (impl), 683eef7 (fix: reason values, HTTP 201)
- **Review notes:** Fixed allowed reasons to match spec, added 201 status code.

## Task 4: Flags Admin Page
- **Status:** DONE
- **Commits:** 3e3113a (impl), 683eef7 (fix: admin links, action2, ORDER BY, admin notice)
- **Review notes:** Fixed finding title links, bottom dropdown action2, unreviewed-first sort, added admin notice.

## Task 5: Flag Button in Card UI
- **Status:** DONE
- **Commits:** a2db4c5 (impl)
- **Review notes:** Approved clean.
