<?php
namespace SteemDB\Controllers;

use SteemDB\Models\Account;
use SteemDB\Models\Block30d;
use SteemDB\Models\Comment;
use SteemDB\Models\Vote;
use MongoDB\BSON\UTCDateTime;

class LabsController extends ControllerBase
{
  public function indexAction()
  {

  }
  public function rsharesAction() {
    $this->view->date = $date = strtotime($this->request->get("date") ?: date("Y-m-d"));
    $dates = [
      '$gte' => new UTCDateTime($date * 1000),
      '$lt' => new UTCDateTime(($date + 86400) * 1000),
    ];
    $this->view->data = Comment::rsharesAllocation($dates)->toArray();
  }
  public function powerupAction() {
    // {transactions: {$elemMatch: {'operations.0.0': 'transfer_to_vesting'}}
    $transactions = Block30d::aggregate([
      [
        '$match' => [
          'transactions' => [
            '$elemMatch' => ['operations.0.0' => 'transfer_to_vesting']
          ]
        ]
      ],
      [
        '$unwind' => '$transactions'
      ],
      [
        '$unwind' => '$transactions.operations',
      ],
      [
        '$match' => [
          'transactions.operations.0' => 'transfer_to_vesting'
        ]
      ],
      [
        '$unwind' => '$transactions.operations',
      ],
      [
        '$match' => [
          'transactions.operations.to' => ['$exists' => true]
        ]
      ],
      [
        '$project' => [
          'target' => '$transactions.operations',
          'date' => [
            'doy' => ['$dayOfYear' => '$_ts'],
            'year' => ['$year' => '$_ts'],
            'month' => ['$month' => '$_ts'],
            'day' => ['$dayOfMonth' => '$_ts'],
          ],
        ]
      ],
      [
        '$group' => [
          '_id' => [
            'user' => '$target.to',
          ],
          'count' => ['$sum' => 1],
          'instances' => ['$addToSet' => '$target.amount']
        ],
      ],
      [
        '$lookup' => [
          'from' => 'account',
          'localField' => '_id.user',
          'foreignField' => 'name',
          'as' => 'account'
        ]
      ]
    ])->toArray();
    foreach($transactions as $idx => $tx) {
      $transactions[$idx]['total'] = 0;
      foreach($tx['instances'] as $powerup) {
        $transactions[$idx]['total'] += (float) explode(" ", $powerup)[0];
      }
    }
    usort($transactions, function($a, $b) {
      return $b['total'] - $a['total'];
    });
    $this->view->powerups = $transactions;
  }
  public function votefocusingAction() {
    $this->view->focus = Vote::Aggregate([
      [
        '$match' => [
          '_ts' => [
            '$gte' => new UTCDateTime(strtotime("-30 days") * 1000),
            '$lte' => new UTCDateTime(strtotime("midnight") * 1000),
          ],
          'weight' => [
            '$gt' => 500
          ]
        ]
      ],
      [
        '$group' => [
          '_id' => [
            'permlink' => '$permlink',
            'voter' => '$voter',
            'author' => '$author'
          ],
          'weight' => ['$avg' => '$weight']
        ]
      ],
      [
        '$project' => [
          '_id' => true,
          'weight' => true,
          'voterisauthor' => ['$eq' => ['$_id.voter', '$_id.author']],
        ]
      ],
      [
        '$match' => [
          'voterisauthor' => false
        ]
      ],
      [
        '$group' => [
          '_id' => [
            'voter' => '$_id.voter',
            'author' => '$_id.author'
          ],
          'count' => ['$sum' => 1],
          'weight' => ['$avg' => '$weight'],
        ]
      ],
      [
        '$sort' => [
          'count' => -1
        ]
      ],
      [
        '$limit' => 200
      ],
      [
        '$lookup' => [
          'from' => 'account',
          'localField' => '_id.voter',
          'foreignField' => 'name',
          'as' => 'account'
        ]
      ],
    ], [
      'allowDiskUse' => true,
      'cursor' => [
        'batchSize' => 0
      ]
    ])->toArray();
  }
}
