import axios from '../axios'
import queryManager from '../queryManager'

const { queryFn } = queryManager

const get = {
	all: queryFn('getDataFileTypes', () => axios.get('dataFileType'))
}

const queryGroup = {
	get
}

export default queryGroup
