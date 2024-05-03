import axios from '../axios'
import queryManager from '../queryManager'

const { queryFn } = queryManager

const get = {
	received: queryFn('dataRequestGetReceived', (entityId) => axios.get(`dataRequest/entity/${entityId}/received`)),
	sent: queryFn('dataRequestGetSent', (entityId) => axios.get(`dataRequest/entity/${entityId}/sent`))
}

const create = queryFn('dataRequestCreate', (entityId, payload) => axios.post(`dataRequest/createFrom/${entityId}`, payload))

const queryGroup = {
	get,
	create
}

export default queryGroup
