<?php

namespace app\modules\admin\modules\news\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * NewsSearch represents the model behind the search form of `app\modules\admin\modules\news\models\News`.
 */
class NewsSearch extends News
{

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'category_id', 'is_picture_news', 'enabled', 'enabled_comment', 'published_at'], 'integer'],
            [['title', 'keywords', 'author', 'source',], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = News::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC]
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'category_id' => $this->category_id,
            'is_picture_news' => $this->is_picture_news,
            'enabled' => $this->enabled,
            'enabled_comment' => $this->enabled_comment,
            'published_at' => $this->published_at,
        ]);

        $query->andFilterWhere(['like', 'title', $this->title])
            ->andFilterWhere(['like', 'keywords', $this->keywords])
            ->andFilterWhere(['like', 'author', $this->author])
            ->andFilterWhere(['like', 'source', $this->source]);

        return $dataProvider;
    }
}
