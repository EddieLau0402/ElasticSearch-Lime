<?php
namespace Eddie\ElasticSearch;

class Es
{
    use Queryable, Searchable;


    protected $index;

    protected $type;

    protected $client;

    protected $body;


    public function __construct(array $option)
    {
        $clientBuilder = \Elasticsearch\ClientBuilder::create();

        // Set option
        $clientBuilder
            ->setHosts($option['hosts'])
        ;
        $this->client = $clientBuilder->build();

        // Set index
        if (isset($option['index']) && !empty($option['index'])) $this->setIndex($option['index']);
        // Set type
        $this->setType($option['type'] ?? 'doc');
    }


    /**
     * 创建索引
     *
     * @author Eddie
     *
     * @param string $index
     * @param array $option
     * @return bool
     */
    public function createIndex(string $index, array $option = [])
    {
        $params = [
            'index' => $index,
            //'body' => [],
        ];

        // Setting option of index
        if (!empty($option)) {
            if (isset($option['settings']) && is_array($option['settings'])) {
                $params['body']['settings'] = $option['settings'];
            }
            if (isset($option['numbber_of_shards']) && is_numeric($option['numbber_of_shards'])) {
                $params['body']['settings']['number_of_shards'] = $option['numbber_of_shards'];
            }
            if (isset($option['number_of_replicas']) && is_numeric($option['number_of_replicas'])) {
                $params['body']['settings']['number_of_replicas'] = $option['number_of_replicas'];
            }
        }

        $rep = $this->client->indices()->create($params);
        $ret = !!$rep['acknowledged'];

        if ($ret) $this->setIndex($index); // Set current index

        return $ret;
    }

    /**
     * 删除索引
     *
     * @author Eddie
     *
     * @param string $index
     * @return array
     */
    public function deleteIndex(string $index = '')
    {
        if (empty($index)) $index = $this->index;

        $rep = $this->client->indices()->delete(['index' => $index]);
        $ret = !!$rep['acknowledged'];

        if ($ret) $this->index = null; // Unset current index

        return $ret;
    }

    /**
     * 索引一个文档
     *
     * @author Eddie
     *
     * @param array $data
     * @return array
     */
    public function createDocument(array $data)
    {
        $params = [
            'index' => $this->getIndex(),
            'type' => $this->getType(),
        ];
        if (isset($data['id'])) {
            $params['id'] = $data['id'];
            unset($data['id']);
        }
        $params['body'] = $data;

        return $this->client->index($params);
    }

    /**
     * 删除一个文档
     *
     * @author Eddie
     *
     * @param string|int $id
     * @return array
     */
    public function deleteDocument($id)
    {
        return $this->client->delete([
            'index' => $this->getIndex(),
            'type' => $this->getType(),
            'id' => $id
        ]);
    }

    /**
     * 获取一个文档
     *
     * @author Eddie
     *
     * @param string|int $id
     * @return array
     */
    public function getDocument($id)
    {
        return $this->client->get([
            'index' => $this->getIndex(),
            'type' => $this->getType(),
            'id' => $id
        ]);
    }


    public function __call($method, $args)
    {
        if ($this->canCallQueryMethods($method)) {
            call_user_func_array([$this->getQuery(), $method], $args);
            return $this;
        } else {

            switch (strtolower($method)) {
                case 'wheregt':
                case 'wheregte':
                case 'wherelt':
                case 'wherelte':
                case 'wherebetween':
                case 'orwheregt':
                case 'orwheregte':
                case 'orwherelt':
                case 'orwherelte':
                case 'orwherebetween':
                case 'wherenotgt':
                case 'wherenotgte':
                case 'wherenotlt':
                case 'wherenotlte':
                case 'wherenotbetween':
                    call_user_func_array([$this->getQuery(), $method], $args);
                    return $this;
                    break;
            }

        }
    }


    /**
     * @return mixed
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param mixed $index
     */
    public function setIndex($index)
    {
        $this->index = $index;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

}