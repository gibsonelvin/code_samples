import axios from '../axios'
import queryManager from '../queryManager'

const { queryFn } = queryManager

const get = {
	byDataRequestId: queryFn('dataRequestConstituentsGetByID', (dataRequest) => axios.get(`dataRequestConstituent/dataRequest/${dataRequest.id}`)),
	rejectAttachment: queryFn('rejectConstituent', (dataRequestId) => axios.patch(`dataRequestConstituent/reject/${dataRequestId}`)),
	acceptAttachment: queryFn('acceptConstituent', (dataRequestId) => axios.patch(`dataRequestConstituent/accept/${dataRequestId}`))
}

const post = {
	attachment: queryFn('attachConstituent', (dataRequestId, payload) => axios.post(`dataRequestConstituent/attach/${dataRequestId}`, payload))
}

const queryGroup = {
	get,
	post
}

export default queryGroup
